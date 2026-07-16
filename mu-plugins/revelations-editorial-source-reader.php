<?php
/**
 * Plugin Name: REVELATIONS Editorial Source Reader
 * Description: Safely extracts editorial source text for AI drafting.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize extracted source text.
 */
function revelations_editorial_normalize_source_text(
    string $text
): string {
    $text = html_entity_decode(
        $text,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $text = wp_strip_all_tags(
        $text,
        true
    );

    $text = preg_replace(
        '/[\x{00A0}\s]+/u',
        ' ',
        $text
    );

    return is_string( $text )
        ? trim( $text )
        : '';
}

/**
 * Remove advertising and recommendation content appended
 * after the actual article.
 */
function revelations_editorial_trim_source_tail(
    string $text
): string {
    $stop_fragments = array(
        'when you purchase through links in our articles',
        'this doesn’t affect our editorial independence',
        "this doesn't affect our editorial independence",
        'last chance to save',
        'savings end',
    );

    $cut_position = null;

    foreach ( $stop_fragments as $fragment ) {
        $position = mb_stripos(
            $text,
            $fragment,
            0,
            'UTF-8'
        );

        if (
            false !== $position &&
            (
                null === $cut_position ||
                $position < $cut_position
            )
        ) {
            $cut_position = $position;
        }
    }

    if ( null !== $cut_position ) {
        $text = mb_substr(
            $text,
            0,
            $cut_position,
            'UTF-8'
        );
    }

    return trim( $text );
}

/**
 * Find articleBody recursively inside JSON-LD data.
 *
 * @param mixed $value Decoded JSON-LD value.
 */
function revelations_editorial_find_jsonld_article_body(
    mixed $value
): string {
    if ( ! is_array( $value ) ) {
        return '';
    }

    $type = $value['@type'] ?? '';

    $types = is_array( $type )
        ? $type
        : array( $type );

    $article_types = array(
        'Article',
        'NewsArticle',
        'BlogPosting',
        'TechArticle',
        'Report',
    );

    $is_article = array_intersect(
        $article_types,
        array_map(
            'strval',
            $types
        )
    ) !== array();

    if (
        $is_article &&
        isset( $value['articleBody'] ) &&
        is_string( $value['articleBody'] )
    ) {
        $body =
            revelations_editorial_normalize_source_text(
                $value['articleBody']
            );

        if ( mb_strlen( $body, 'UTF-8' ) >= 400 ) {
            return $body;
        }
    }

    foreach ( $value as $child ) {
        if ( ! is_array( $child ) ) {
            continue;
        }

        $found =
            revelations_editorial_find_jsonld_article_body(
                $child
            );

        if ( '' !== $found ) {
            return $found;
        }
    }

    return '';
}

/**
 * Extract meaningful paragraphs from HTML.
 *
 * @return array{
 *     ok:bool,
 *     method:string,
 *     text:string,
 *     characters:int,
 *     paragraphs:int,
 *     error:string
 * }
 */
function revelations_editorial_extract_source_html(
    string $html
): array {
    $failure = array(
        'ok'         => false,
        'method'     => 'none',
        'text'       => '',
        'characters' => 0,
        'paragraphs' => 0,
        'error'      => '',
    );

    if ( '' === trim( $html ) ) {
        $failure['error'] = 'Source HTML is empty.';
        return $failure;
    }

    if ( ! class_exists( 'DOMDocument' ) ) {
        $failure['error'] =
            'PHP DOM extension is unavailable.';

        return $failure;
    }

    $dom = new DOMDocument();

    $previous_errors =
        libxml_use_internal_errors( true );

    $loaded = $dom->loadHTML(
        '<?xml encoding="utf-8" ?>' . $html,
        LIBXML_NOERROR |
        LIBXML_NOWARNING |
        LIBXML_NONET
    );

    libxml_clear_errors();
    libxml_use_internal_errors(
        $previous_errors
    );

    if ( ! $loaded ) {
        $failure['error'] =
            'Source HTML could not be parsed.';

        return $failure;
    }

    $xpath = new DOMXPath( $dom );

    /*
     * First preference: explicit articleBody in JSON-LD.
     */
    $scripts = $xpath->query(
        '//script[
            contains(
                translate(
                    @type,
                    "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
                    "abcdefghijklmnopqrstuvwxyz"
                ),
                "ld+json"
            )
        ]'
    );

    if ( $scripts instanceof DOMNodeList ) {
        foreach ( $scripts as $script ) {
            $json = trim(
                (string) $script->textContent
            );

            if ( '' === $json ) {
                continue;
            }

            $decoded = json_decode(
                $json,
                true
            );

            if ( ! is_array( $decoded ) ) {
                continue;
            }

            $article_body =
                revelations_editorial_find_jsonld_article_body(
                    $decoded
                );

            if ( '' !== $article_body ) {
                $article_body = mb_substr(
                    $article_body,
                    0,
                    20000,
                    'UTF-8'
                );

                return array(
                    'ok'         => true,
                    'method'     => 'json_ld_article_body',
                    'text'       => $article_body,
                    'characters' => mb_strlen(
                        $article_body,
                        'UTF-8'
                    ),
                    'paragraphs' => 1,
                    'error'      => '',
                );
            }
        }
    }

    /*
     * Second preference: paragraphs inside article/main.
     * Final fallback: all page paragraphs.
     */
    $queries = array(
        'article_paragraphs' =>
            '//article//p',

        'main_paragraphs' =>
            '//main//p',

        'page_paragraphs' =>
            '//p',
    );

    $blocked_fragments = array(
        'accept cookies',
        'cookie policy',
        'privacy policy',
        'terms of service',
        'sign up for',
        'subscribe to',
        'newsletter',
        'all rights reserved',
        'follow us on',
        'advertisement',
        'related articles',
        'read more:',
    );

    /*
     * These fragments usually mark the end of the article.
     * Everything after them is commonly advertising,
     * recommendations or unrelated page content.
     */
    $stop_fragments = array(
        'when you purchase through links in our articles',
        'this doesn’t affect our editorial independence',
        "this doesn't affect our editorial independence",
        'last chance to save',
        'savings end',
    );

    foreach ( $queries as $method => $query ) {
        $nodes = $xpath->query( $query );

        if (
            ! $nodes instanceof DOMNodeList ||
            0 === $nodes->length
        ) {
            continue;
        }

        $paragraphs = array();
        $seen       = array();

        foreach ( $nodes as $node ) {
            $paragraph =
                revelations_editorial_normalize_source_text(
                    (string) $node->textContent
                );

            $length = mb_strlen(
                $paragraph,
                'UTF-8'
            );

            if ( $length < 45 ) {
                continue;
            }

            $lower = mb_strtolower(
                $paragraph,
                'UTF-8'
            );

            $blocked = false;

            foreach ( $blocked_fragments as $fragment ) {
                if ( str_contains( $lower, $fragment ) ) {
                    $blocked = true;
                    break;
                }
            }

            if ( $blocked ) {
                continue;
            }

            $duplicate_key = md5( $lower );

            if ( isset( $seen[ $duplicate_key ] ) ) {
                continue;
            }

            $seen[ $duplicate_key ] = true;
            $paragraphs[]           = $paragraph;

            if (
                mb_strlen(
                    implode( "\n\n", $paragraphs ),
                    'UTF-8'
                ) >= 20000
            ) {
                break;
            }
        }

        $text = trim(
            implode(
                "\n\n",
                $paragraphs
            )
        );

        if (
            count( $paragraphs ) >= 3 &&
            mb_strlen( $text, 'UTF-8' ) >= 500
        ) {
            $text = mb_substr(
                $text,
                0,
                20000,
                'UTF-8'
            );

            return array(
                'ok'         => true,
                'method'     => $method,
                'text'       => $text,
                'characters' => mb_strlen(
                    $text,
                    'UTF-8'
                ),
                'paragraphs' => count(
                    $paragraphs
                ),
                'error'      => '',
            );
        }
    }

    $failure['error'] =
        'No sufficiently complete article text was found.';

    return $failure;
}

/**
 * Fetch and extract one public source page.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_fetch_source_text(
    string $source_url
): array {
    $source_url = esc_url_raw(
        $source_url,
        array( 'https' )
    );

    if ( '' === $source_url ) {
        return array(
            'ok'          => false,
            'http_status' => 0,
            'method'      => 'none',
            'text'        => '',
            'characters'  => 0,
            'paragraphs'  => 0,
            'error'       =>
                'Source URL must use HTTPS.',
        );
    }

    $started = microtime( true );

    /*
     * wp_safe_remote_get protects against unsafe internal URLs.
     */
    $response = wp_safe_remote_get(
        $source_url,
        array(
            'timeout'             => 30,
            'redirection'         => 3,
            'limit_response_size' => 2 * MB_IN_BYTES,

            'headers' => array(
                'Accept' =>
                    'text/html,application/xhtml+xml',

                'User-Agent' =>
                    'REVELATIONS Editorial Desk/0.1 '
                    . '(https://revelations.me)',
            ),
        )
    );

    $duration_ms = (int) round(
        ( microtime( true ) - $started ) * 1000
    );

    if ( is_wp_error( $response ) ) {
        return array(
            'ok'          => false,
            'http_status' => 0,
            'duration_ms' => $duration_ms,
            'method'      => 'none',
            'text'        => '',
            'characters'  => 0,
            'paragraphs'  => 0,
            'error'       =>
                $response->get_error_message(),
        );
    }

    $http_status = (int)
        wp_remote_retrieve_response_code(
            $response
        );

    if (
        $http_status < 200 ||
        $http_status >= 300
    ) {
        return array(
            'ok'          => false,
            'http_status' => $http_status,
            'duration_ms' => $duration_ms,
            'method'      => 'none',
            'text'        => '',
            'characters'  => 0,
            'paragraphs'  => 0,
            'error'       =>
                'Source returned HTTP ' .
                $http_status . '.',
        );
    }

    $content_type = strtolower(
        (string)
        wp_remote_retrieve_header(
            $response,
            'content-type'
        )
    );

    if (
        '' !== $content_type &&
        ! str_contains(
            $content_type,
            'text/html'
        ) &&
        ! str_contains(
            $content_type,
            'application/xhtml+xml'
        )
    ) {
        return array(
            'ok'          => false,
            'http_status' => $http_status,
            'duration_ms' => $duration_ms,
            'method'      => 'none',
            'text'        => '',
            'characters'  => 0,
            'paragraphs'  => 0,
            'error'       =>
                'Source did not return HTML.',
        );
    }

    $result =
        revelations_editorial_extract_source_html(
            wp_remote_retrieve_body(
                $response
            )
        );

    if (
        ! empty( $result['ok'] ) &&
        isset( $result['text'] ) &&
        is_string( $result['text'] )
    ) {
        $original_text =
            $result['text'];

        $clean_text =
            revelations_editorial_trim_source_tail(
                $original_text
            );

        $result['text'] =
            $clean_text;

        $result['characters'] =
            mb_strlen(
                $clean_text,
                'UTF-8'
            );

        $paragraphs = array_values(
            array_filter(
                preg_split(
                    '/\R{2,}/u',
                    trim( $clean_text )
                )
            )
        );

        $result['paragraphs'] =
            count( $paragraphs );

        $result['tail_trimmed'] =
            $clean_text !== $original_text;

        if (
            mb_strlen(
                $clean_text,
                'UTF-8'
            ) < 500
        ) {
            $result = array(
                'ok'           => false,
                'method'       =>
                    $result['method'] ?? 'none',
                'text'         => '',
                'characters'   => 0,
                'paragraphs'   => 0,
                'tail_trimmed' => true,
                'error'        =>
                    'Source text became too short after cleanup.',
            );
        }
    }

    $result['http_status'] =
        $http_status;

    $result['duration_ms'] =
        $duration_ms;

    return $result;
}
