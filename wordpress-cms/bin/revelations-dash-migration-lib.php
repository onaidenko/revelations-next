<?php
declare(strict_types=1);

/**
 * Pure helpers for the byte-preserving editorial dash migration.
 *
 * This file deliberately has no WordPress bootstrap and performs no writes.
 */

function revelations_dash_normalize_segment(string $value): array {
    $pattern = '~https?://[^\s<>"\']+|[ \t]*[\x{2013}\x{2014}][ \t]*~u';
    $cursor = 0;
    $after = '';
    $replacements = array();
    $protected = array();
    if (!preg_match_all($pattern, $value, $matches, PREG_OFFSET_CAPTURE)) {
        return array('after' => $value, 'replacements' => array(), 'protected' => array());
    }
    foreach ($matches[0] as $match) {
        [$token, $offset] = $match;
        $after .= substr($value, $cursor, $offset - $cursor);
        if (preg_match('~^https?://~i', $token)) {
            $after .= $token;
            if (preg_match('/[\x{2013}\x{2014}]/u', $token)) {
                $protected[] = array('offset' => $offset, 'value' => $token, 'reason' => 'url');
            }
        } elseif (preg_match('/[\x{2013}\x{2014}]/u', $token)) {
            $after_offset = strlen($after);
            $after .= ' - ';
            $replacements[] = array(
                'before_offset' => $offset,
                'before_length' => strlen($token),
                'after_offset' => $after_offset,
                'before' => $token,
                'after' => ' - ',
                'character' => str_contains($token, "\u{2014}") ? "\u{2014}" : "\u{2013}",
            );
        }
        $cursor = $offset + strlen($token);
    }
    $after .= substr($value, $cursor);
    return array('after' => $after, 'replacements' => $replacements, 'protected' => $protected);
}

function revelations_dash_normalize(string $value, bool $html): array {
    if (!$html) return revelations_dash_normalize_segment($value);

    $parts = preg_split(
        '~(<!--.*?-->|<![^>]*>|<[^>]*>)~s',
        $value,
        -1,
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE
    );
    if (!is_array($parts)) throw new RuntimeException('Unable to tokenize HTML');
    $after = '';
    $replacements = array();
    $protected = array();
    $blocked_depth = 0;
    foreach ($parts as [$part, $offset]) {
        $is_markup = str_starts_with($part, '<');
        if ($is_markup || $blocked_depth > 0) {
            $after .= $part;
            if (preg_match_all('/[\x{2013}\x{2014}]/u', $part, $dashes, PREG_OFFSET_CAPTURE)) {
                foreach ($dashes[0] as [$dash, $local]) {
                    $protected[] = array(
                        'offset' => $offset + $local,
                        'value' => $dash,
                        'reason' => $is_markup ? 'markup-or-block-comment' : 'code-like-element',
                    );
                }
            }
            if ($is_markup && preg_match('~^\s*<\s*(pre|code|script|style)(?:\s|>)~i', $part)) $blocked_depth++;
            if ($is_markup && preg_match('~^\s*<\s*/\s*(pre|code|script|style)\s*>~i', $part)) $blocked_depth = max(0, $blocked_depth - 1);
            continue;
        }
        $normalized = revelations_dash_normalize_segment($part);
        $base_after = strlen($after);
        foreach ($normalized['replacements'] as $replacement) {
            $replacement['before_offset'] += $offset;
            $replacement['after_offset'] += $base_after;
            $replacements[] = $replacement;
        }
        foreach ($normalized['protected'] as $item) {
            $item['offset'] += $offset;
            $protected[] = $item;
        }
        $after .= $normalized['after'];
    }
    return array('after' => $after, 'replacements' => $replacements, 'protected' => $protected);
}

function revelations_dash_assert_invariant(string $before, array $result): string {
    $rebuilt = '';
    $before_cursor = 0;
    foreach ($result['replacements'] as $replacement) {
        $rebuilt .= substr($before, $before_cursor, $replacement['before_offset'] - $before_cursor);
        $rebuilt .= $replacement['after'];
        $before_cursor = $replacement['before_offset'] + $replacement['before_length'];
    }
    $rebuilt .= substr($before, $before_cursor);
    if ($rebuilt !== $result['after']) throw new RuntimeException('Dash-only replay invariant failed');

    $before_untouched = '';
    $after_untouched = '';
    $before_cursor = 0;
    $after_cursor = 0;
    foreach ($result['replacements'] as $replacement) {
        $before_untouched .= substr($before, $before_cursor, $replacement['before_offset'] - $before_cursor);
        $after_untouched .= substr($result['after'], $after_cursor, $replacement['after_offset'] - $after_cursor);
        $before_cursor = $replacement['before_offset'] + $replacement['before_length'];
        $after_cursor = $replacement['after_offset'] + strlen($replacement['after']);
    }
    $before_untouched .= substr($before, $before_cursor);
    $after_untouched .= substr($result['after'], $after_cursor);
    if ($before_untouched !== $after_untouched) throw new RuntimeException('Untouched-byte invariant failed');
    return hash('sha256', $before_untouched);
}

function revelations_dash_context(string $before, string $after, array $replacement): array {
    $radius = 70;
    return array(
        'before' => substr($before, max(0, $replacement['before_offset'] - $radius), $replacement['before_length'] + 2 * $radius),
        'after' => substr($after, max(0, $replacement['after_offset'] - $radius), strlen($replacement['after']) + 2 * $radius),
    );
}
