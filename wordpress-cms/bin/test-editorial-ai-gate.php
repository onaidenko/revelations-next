<?php
/**
 * Isolated diagnostics for the global editorial AI gate.
 *
 * Loads no WordPress runtime, database, feeds or network resources
 * and creates no editorial records.
 */

declare(strict_types=1);

if ( ! function_exists( 'mb_strtolower' ) ) {
    fwrite( STDERR, "FAIL: PHP mbstring is required.\n" );
    exit( 1 );
}

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

require_once dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-scanner-engine.php';

/*
 * Each case contains:
 * name, title, summary, expected qualification and optional
 * expected counts keyed by an AI gate result field.
 */
$cases = array(
    array(
        'reject ordinary hotel',
        'A new luxury hotel opens in Dubai',
        'The resort features restaurants, pools and new architecture.',
        false,
    ),
    array(
        'reject incidental AI hotel mention',
        'A new luxury hotel opens in Dubai',
        'The hotel also mentions AI among its guest services.',
        false,
    ),
    array(
        'reject Ai Weiwei false positive',
        'Ai Weiwei opens a new exhibition',
        'The artist discusses architecture and culture.',
        false,
    ),
    array(
        'reject Nvidia infrastructure without AI subject',
        'Nvidia opens a data center in France',
        'The facility will use GPUs for cloud workloads.',
        false,
    ),
    array(
        'reject robot without AI context',
        'Restaurant installs robot waiter',
        'The machine carries plates between the kitchen and tables.',
        false,
        array( 'direct_matches' => 0 ),
    ),
    array(
        'reject incidental AI concierge',
        'Hotel renovation adds spa, restaurants and AI concierge',
        'The main project is a large architectural redesign.',
        false,
    ),
    array(
        'reject brief generative AI reference',
        'Bank announces internal reorganization',
        'The announcement briefly references generative AI.',
        false,
        array( 'direct_matches' => 1 ),
    ),
    array(
        'accept AI hotel transformation',
        'AI transforms hotel operations in Dubai',
        'A predictive platform automates energy and staffing.',
        true,
    ),
    array(
        'accept summary-only OpenAI launch',
        'Enterprise software market shifts',
        'OpenAI launches a model with new inference tools and agents.',
        true,
    ),
    array(
        'accept Anthropic Claude model launch',
        'Anthropic launches a new Claude model',
        'The release expands enterprise agent and reasoning capabilities.',
        true,
    ),
    array(
        'accept AI-generated exhibition',
        'Museum introduces an AI-generated interactive exhibition',
        'Generative models adapt the installation to visitor behaviour.',
        true,
    ),
    array(
        'deduplicate nested generative AI signal',
        'Market outlook changes',
        'The report briefly references generative AI.',
        false,
        array( 'direct_matches' => 1 ),
    ),
    array(
        'deduplicate Google DeepMind signal',
        'Industry names appear in annual survey',
        'Google DeepMind is listed among many organizations.',
        false,
        array( 'direct_matches' => 1 ),
    ),
    array(
        'keep humanoid robots supporting-only',
        'Humanoid robots arrive at restaurant',
        'The machines carry trays for staff.',
        false,
        array(
            'direct_matches'    => 0,
            'technical_matches' => 1,
        ),
    ),
    array(
        'reject Gemini without AI context',
        'Gemini program opens summer applications',
        'The cultural exchange is available to university students.',
        false,
        array(
            'direct_matches'     => 0,
            'contextual_matches' => 1,
        ),
    ),
    array(
        'reject Siri without AI context',
        'Siri voice actor attends a media event',
        'The discussion covers performance and contract work.',
        false,
        array( 'direct_matches' => 0 ),
    ),
    array(
        'reject self-driving without AI context',
        'Self-driving shuttle begins a new route',
        'The autonomous vehicle carries passengers across campus.',
        false,
        array( 'direct_matches' => 0 ),
    ),
    array(
        'reject one incidental AI mention',
        'Quarterly retail report is published',
        'A footnote mentions AI once.',
        false,
    ),
    array(
        'accept contextual Gemini with AI evidence',
        'Google launches Gemini for enterprise teams',
        'The model adds multimodal reasoning and inference.',
        true,
    ),
    array(
        'accept contextual Claude with AI evidence',
        'Claude launches new enterprise tools',
        'The model adds agents, inference and reasoning.',
        true,
    ),
    array(
        'accept Google DeepMind model release',
        'Google DeepMind releases a new model',
        'The system expands multimodal reasoning capabilities.',
        true,
        array( 'direct_matches' => 1 ),
    ),
    array(
        'accept Midjourney model release',
        'Midjourney releases a new image model',
        'The platform expands image generation controls.',
        true,
    ),
    array(
        'accept Stable Diffusion model release',
        'Stable Diffusion releases a new model',
        'The system expands image generation quality.',
        true,
    ),
);

$future_tech_cases = array(
    array( 'accept autonomous airport shuttle', 'Autonomous shuttle begins passenger service at airport', 'The operational fleet uses lidar sensors and an autonomous control system.', true ),
    array( 'accept humanoid hotel service', 'Humanoid robots begin operating a hotel', 'The robotic service fleet is deployed with computer-controlled systems.', true ),
    array( 'accept printed housing', "World's largest 3D-printed housing project completes first homes", 'Additive construction robots completed production homes with automated control systems.', true ),
    array( 'accept robotic construction', 'Robotic construction system begins building homes autonomously', 'The construction robot is deployed in an operational automated production pilot.', true ),
    array( 'accept smart-city transit', 'Smart-city district launches autonomous transport network', 'An operational autonomous fleet uses lidar and an intelligent transport system.', true ),
    array( 'accept air taxi service', 'eVTOL air taxi begins commercial passenger service', 'The operational fleet begins service with autonomous flight-control systems.', true ),
    array( 'reject luxury hotel', 'Luxury hotel opens after $200 million renovation', 'The resort adds restaurants and a spa.', false ),
    array( 'reject concept hotel', 'Architect unveils futuristic hotel concept', 'The design study includes new renderings.', false ),
    array( 'reject smart-looking interior', 'Inside the smart-looking interiors of a new resort', 'A luxury design project opens this year.', false ),
    array( 'reject planned city', 'Developer plans futuristic city', 'The concept may transform the district.', false ),
    array( 'reject speculative architecture', 'The future of architecture could be robotic', 'A commentary imagines possible buildings.', false ),
    array( 'reject generic technology promise', 'New technology promises to transform hotels', 'The company plans a future platform.', false ),
);

$failures = 0;

foreach ( $cases as $case ) {
    $result = revelations_editorial_scanner_ai_gate(
        array(
            'title'   => (string) $case[1],
            'summary' => (string) $case[2],
        )
    );

    $errors = array();

    $actual_qualified =
        true === ( $result['qualified'] ?? false );

    if ( (bool) $case[3] !== $actual_qualified ) {
        $errors[] = sprintf(
            'qualified expected %s, got %s',
            $case[3] ? 'true' : 'false',
            $actual_qualified ? 'true' : 'false'
        );
    }

    $expected_counts = isset( $case[4] ) &&
        is_array( $case[4] )
            ? $case[4]
            : array();

    foreach ( $expected_counts as $result_key => $expected_count ) {
        $actual_count = count(
            isset( $result[ $result_key ] ) &&
            is_array( $result[ $result_key ] )
                ? $result[ $result_key ]
                : array()
        );

        if ( (int) $expected_count !== $actual_count ) {
            $errors[] = sprintf(
                '%s expected %d, got %d',
                $result_key,
                (int) $expected_count,
                $actual_count
            );
        }
    }

    if ( array() === $errors ) {
        fwrite( STDOUT, 'PASS: ' . (string) $case[0] . "\n" );
        continue;
    }

    $failures++;
    fwrite(
        STDERR,
        'FAIL: ' .
        (string) $case[0] .
        ' — ' .
        implode( '; ', $errors ) .
        "\n"
    );
}

foreach ( $future_tech_cases as $case ) {
    $result = revelations_editorial_scanner_ai_gate(
        array( 'title' => $case[1], 'summary' => $case[2] ),
        'places'
    );
    if ( (bool) $case[3] === ( true === ( $result['qualified'] ?? false ) ) ) {
        fwrite( STDOUT, 'PASS: ' . $case[0] . "\n" );
        continue;
    }
    $failures++;
    fwrite( STDERR, 'FAIL: ' . $case[0] . "\n" );
}

$ai_branch = revelations_editorial_scanner_ai_gate(
    array(
        'title' => 'OpenAI launches a new model',
        'summary' => 'The model adds inference tools and agents.',
    ),
    'tech'
);
if ( 'ai' === ( $ai_branch['gate_branch'] ?? '' ) ) {
    fwrite( STDOUT, "PASS: existing AI story retains AI branch\n" );
} else {
    $failures++;
    fwrite( STDERR, "FAIL: existing AI story retains AI branch\n" );
}

if ( $failures > 0 ) {
    fwrite(
        STDERR,
        sprintf(
            "%d of %d AI gate diagnostics failed.\n",
            $failures,
            count( $cases ) + count( $future_tech_cases ) + 1
        )
    );
    exit( 1 );
}

fwrite(
    STDOUT,
    sprintf(
        "All %d AI gate diagnostics passed.\n",
        count( $cases ) + count( $future_tech_cases ) + 1
    )
);

exit( 0 );
