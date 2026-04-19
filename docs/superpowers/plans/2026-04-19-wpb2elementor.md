# WPB2Elementor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a standalone WordPress plugin that converts WPBakery shortcodes to Elementor JSON via Admin UI, with static mapping, optional Claude API, and prompt-export fallback.

**Architecture:** PHP-only plugin with 5 classes: Parser (shortcode → PHP tree), Mapper (static WPBakery→Elementor lookup), Converter (PHP tree → Elementor JSON), ClaudeAPI (HTTP requests for unknown widgets), AdminUI (WP admin page + AJAX). No JavaScript build step required.

**Tech Stack:** PHP 7.4+, WordPress 6.0+, Elementor Pro 3+, wp_remote_post() for Claude API, WordPress Settings API for options.

---

## File Structure

```
wpb2elementor/
├── wpb2elementor.php          # Plugin header, autoload, hook registration
├── includes/
│   ├── class-parser.php       # Parse WPBakery shortcode string → nested PHP array
│   ├── class-mapper.php       # Static map + unknown widget fallback logic
│   ├── class-converter.php    # Nested PHP array → Elementor JSON array
│   ├── class-claude-api.php   # Send shortcode to Claude API, get Elementor JSON
│   └── class-admin-ui.php     # Admin page, settings, AJAX handlers
├── assets/
│   └── admin.css              # Minimal table + button styling
└── tests/
    ├── test-parser.php        # Unit tests for Parser
    ├── test-mapper.php        # Unit tests for Mapper
    └── test-converter.php     # Unit tests for Converter
```

---

## Task 1: Plugin Scaffold + Repo Setup

**Files:**
- Create: `wpb2elementor/wpb2elementor.php`

- [ ] **Step 1: Create plugin directory and main file**

```bash
mkdir -p ~/Desktop/wpb2elementor/includes
mkdir -p ~/Desktop/wpb2elementor/assets
mkdir -p ~/Desktop/wpb2elementor/tests
```

Create `wpb2elementor.php`:

```php
<?php
/**
 * Plugin Name: WPB2Elementor
 * Description: Convert WPBakery shortcodes to Elementor JSON.
 * Version: 1.0.0
 * Author: Alexander Kaiser
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPB2EL_VERSION', '1.0.0' );
define( 'WPB2EL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPB2EL_URL', plugin_dir_url( __FILE__ ) );

require_once WPB2EL_PATH . 'includes/class-parser.php';
require_once WPB2EL_PATH . 'includes/class-mapper.php';
require_once WPB2EL_PATH . 'includes/class-converter.php';
require_once WPB2EL_PATH . 'includes/class-claude-api.php';
require_once WPB2EL_PATH . 'includes/class-admin-ui.php';

add_action( 'plugins_loaded', function() {
    new WPB2EL_Admin_UI();
} );
```

- [ ] **Step 2: Initialize git repo**

```bash
cd ~/Desktop/wpb2elementor
git init
echo "*.DS_Store" > .gitignore
echo "/vendor/" >> .gitignore
git add .
git commit -m "feat: plugin scaffold"
```

- [ ] **Step 3: Create GitHub repo and push**

```bash
gh repo create wpb2elementor --public --source=. --remote=origin --push
```

---

## Task 2: Parser Class

**Files:**
- Create: `wpb2elementor/includes/class-parser.php`
- Create: `wpb2elementor/tests/test-parser.php`

The parser converts a WPBakery shortcode string into a nested PHP array tree.

**Output format per node:**
```php
[
  'tag'      => 'vc_row',
  'attrs'    => ['css' => '.vc_custom_123{background:#fff}'],
  'content'  => 'raw inner text (if leaf node)',
  'children' => [ /* nested nodes */ ]
]
```

- [ ] **Step 1: Write failing tests**

Create `tests/test-parser.php`:

```php
<?php
require_once __DIR__ . '/../includes/class-parser.php';

function assert_equal($a, $b, $msg = '') {
    if ($a !== $b) {
        echo "FAIL: $msg\n";
        echo "  Expected: " . print_r($b, true) . "\n";
        echo "  Got:      " . print_r($a, true) . "\n";
    } else {
        echo "PASS: $msg\n";
    }
}

$parser = new WPB2EL_Parser();

// Test 1: single self-contained shortcode with text content
$result = $parser->parse('[vc_column_text]Hello World[/vc_column_text]');
assert_equal(count($result), 1, 'single node count');
assert_equal($result[0]['tag'], 'vc_column_text', 'tag name');
assert_equal($result[0]['content'], 'Hello World', 'text content');
assert_equal($result[0]['children'], [], 'no children');

// Test 2: nested shortcodes
$result = $parser->parse('[vc_row][vc_column][vc_column_text]Hi[/vc_column_text][/vc_column][/vc_row]');
assert_equal($result[0]['tag'], 'vc_row', 'outer tag');
assert_equal($result[0]['children'][0]['tag'], 'vc_column', 'middle tag');
assert_equal($result[0]['children'][0]['children'][0]['tag'], 'vc_column_text', 'inner tag');
assert_equal($result[0]['children'][0]['children'][0]['content'], 'Hi', 'inner content');

// Test 3: attributes
$result = $parser->parse('[vc_column width="1/2"][/vc_column]');
assert_equal($result[0]['attrs']['width'], '1/2', 'width attribute');

// Test 4: empty content returns empty array
$result = $parser->parse('');
assert_equal($result, [], 'empty input');

// Test 5: plain text (no shortcodes) returns text node
$result = $parser->parse('Just some text');
assert_equal($result[0]['tag'], '__text__', 'plain text tag');
assert_equal($result[0]['content'], 'Just some text', 'plain text content');
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
cd ~/Desktop/wpb2elementor
php tests/test-parser.php
```
Expected: errors about missing class WPB2EL_Parser.

- [ ] **Step 3: Implement Parser**

Create `includes/class-parser.php`:

```php
<?php

class WPB2EL_Parser {

    public function parse( string $content ): array {
        $content = trim( $content );
        if ( empty( $content ) ) return [];

        $tokens = $this->tokenize( $content );
        return $this->build_tree( $tokens );
    }

    private function tokenize( string $content ): array {
        $pattern = '/(\[\/?\w+(?:[^\]]*)\])/';
        $parts   = preg_split( $pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
        $tokens  = [];

        foreach ( $parts as $part ) {
            $part = trim( $part );
            if ( empty( $part ) ) continue;

            if ( preg_match( '/^\[\/(\w+)\]$/', $part, $m ) ) {
                $tokens[] = [ 'type' => 'close', 'tag' => $m[1] ];
            } elseif ( preg_match( '/^\[(\w+)((?:\s+[^\]]*)?)\]$/', $part, $m ) ) {
                $tokens[] = [
                    'type'  => 'open',
                    'tag'   => $m[1],
                    'attrs' => $this->parse_attrs( $m[2] ),
                ];
            } else {
                $tokens[] = [ 'type' => 'text', 'content' => $part ];
            }
        }

        return $tokens;
    }

    private function build_tree( array $tokens ): array {
        $stack  = [ [] ];
        $i      = 0;

        while ( $i < count( $tokens ) ) {
            $token = $tokens[ $i ];

            if ( $token['type'] === 'open' ) {
                // Look ahead: does a matching close tag exist?
                $has_close = false;
                for ( $j = $i + 1; $j < count( $tokens ); $j++ ) {
                    if ( $tokens[$j]['type'] === 'close' && $tokens[$j]['tag'] === $token['tag'] ) {
                        $has_close = true;
                        break;
                    }
                }

                if ( $has_close ) {
                    $node = [
                        'tag'      => $token['tag'],
                        'attrs'    => $token['attrs'],
                        'content'  => '',
                        'children' => [],
                    ];
                    array_push( $stack, [ 'node' => $node, 'children' => [] ] );
                } else {
                    // Self-closing
                    $node = [
                        'tag'      => $token['tag'],
                        'attrs'    => $token['attrs'],
                        'content'  => '',
                        'children' => [],
                    ];
                    $top = array_pop( $stack );
                    $top[] = $node;
                    array_push( $stack, $top );
                }
            } elseif ( $token['type'] === 'close' ) {
                if ( count( $stack ) > 1 ) {
                    $children_frame = array_pop( $stack );
                    $frame          = array_pop( $stack );

                    if ( isset( $frame['node'] ) ) {
                        $node             = $frame['node'];
                        $node['children'] = isset( $children_frame['node'] )
                            ? array_merge( $frame['children'] ?? [], [ $children_frame ] )
                            : ( $frame['children'] ?? [] );

                        // children_frame IS the children list
                        $node['children'] = $children_frame;
                        $top              = array_pop( $stack );
                        if ( ! is_array( $top ) ) $top = [];
                        $top[]            = $node;
                        array_push( $stack, $top );
                    }
                }
            } elseif ( $token['type'] === 'text' ) {
                $top = array_pop( $stack );
                if ( ! is_array( $top ) ) $top = [];

                if ( isset( $top['node'] ) ) {
                    // We're inside a node frame - add as content
                    $top['node']['content'] .= $token['content'];
                } else {
                    $top[] = [
                        'tag'      => '__text__',
                        'attrs'    => [],
                        'content'  => $token['content'],
                        'children' => [],
                    ];
                }
                array_push( $stack, $top );
            }

            $i++;
        }

        return is_array( $stack[0] ) && ! isset( $stack[0]['node'] ) ? $stack[0] : [];
    }

    private function parse_attrs( string $attr_string ): array {
        $attrs = [];
        preg_match_all( '/(\w+)=["\']([^"\']*)["\']/', $attr_string, $matches, PREG_SET_ORDER );
        foreach ( $matches as $m ) {
            $attrs[ $m[1] ] = $m[2];
        }
        return $attrs;
    }
}
```

- [ ] **Step 4: Run tests and verify they pass**

```bash
php tests/test-parser.php
```
Expected: all PASS lines.

- [ ] **Step 5: Commit**

```bash
git add includes/class-parser.php tests/test-parser.php
git commit -m "feat: add WPBakery shortcode parser"
```

---

## Task 3: Mapper Class

**Files:**
- Create: `wpb2elementor/includes/class-mapper.php`
- Create: `wpb2elementor/tests/test-mapper.php`

Maps a parsed node's `tag` to an Elementor element descriptor.

**Output format:**
```php
[
  'elType'     => 'widget',        // or 'section' / 'column'
  'widgetType' => 'text-editor',   // only when elType = 'widget'
  'known'      => true,            // false = needs Claude API or placeholder
]
```

- [ ] **Step 1: Write failing tests**

Create `tests/test-mapper.php`:

```php
<?php
require_once __DIR__ . '/../includes/class-mapper.php';

function assert_equal($a, $b, $msg = '') {
    if ($a !== $b) {
        echo "FAIL: $msg — expected " . json_encode($b) . " got " . json_encode($a) . "\n";
    } else {
        echo "PASS: $msg\n";
    }
}

$mapper = new WPB2EL_Mapper();

$r = $mapper->map('vc_row');
assert_equal($r['elType'], 'section', 'vc_row → section');
assert_equal($r['known'], true, 'vc_row known');

$r = $mapper->map('vc_column');
assert_equal($r['elType'], 'column', 'vc_column → column');

$r = $mapper->map('vc_column_text');
assert_equal($r['elType'], 'widget', 'vc_column_text elType');
assert_equal($r['widgetType'], 'text-editor', 'vc_column_text widgetType');

$r = $mapper->map('vc_custom_heading');
assert_equal($r['widgetType'], 'heading', 'heading widget');

$r = $mapper->map('vc_single_image');
assert_equal($r['widgetType'], 'image', 'image widget');

$r = $mapper->map('vc_btn');
assert_equal($r['widgetType'], 'button', 'button widget');

$r = $mapper->map('vc_empty_space');
assert_equal($r['widgetType'], 'spacer', 'spacer widget');

$r = $mapper->map('mkdf_something_unknown');
assert_equal($r['known'], false, 'unknown widget');
assert_equal($r['elType'], 'widget', 'unknown fallback elType');
assert_equal($r['widgetType'], 'html', 'unknown fallback widgetType');
```

- [ ] **Step 2: Run to verify fail**

```bash
php tests/test-mapper.php
```

- [ ] **Step 3: Implement Mapper**

Create `includes/class-mapper.php`:

```php
<?php

class WPB2EL_Mapper {

    private static array $map = [
        'vc_row'             => [ 'elType' => 'section' ],
        'vc_row_inner'       => [ 'elType' => 'section' ],
        'vc_column'          => [ 'elType' => 'column' ],
        'vc_column_inner'    => [ 'elType' => 'column' ],
        'vc_column_text'     => [ 'elType' => 'widget', 'widgetType' => 'text-editor' ],
        'vc_custom_heading'  => [ 'elType' => 'widget', 'widgetType' => 'heading' ],
        'vc_single_image'    => [ 'elType' => 'widget', 'widgetType' => 'image' ],
        'vc_btn'             => [ 'elType' => 'widget', 'widgetType' => 'button' ],
        'vc_separator'       => [ 'elType' => 'widget', 'widgetType' => 'divider' ],
        'vc_empty_space'     => [ 'elType' => 'widget', 'widgetType' => 'spacer' ],
        'vc_video'           => [ 'elType' => 'widget', 'widgetType' => 'video' ],
        'vc_gallery'         => [ 'elType' => 'widget', 'widgetType' => 'image-gallery' ],
        'vc_icon'            => [ 'elType' => 'widget', 'widgetType' => 'icon' ],
        'vc_raw_html'        => [ 'elType' => 'widget', 'widgetType' => 'html' ],
        'vc_accordion'       => [ 'elType' => 'widget', 'widgetType' => 'accordion' ],
        'vc_accordion_tab'   => [ 'elType' => 'widget', 'widgetType' => 'accordion' ],
        'vc_tabs'            => [ 'elType' => 'widget', 'widgetType' => 'tabs' ],
        'vc_tab'             => [ 'elType' => 'widget', 'widgetType' => 'tabs' ],
        'vc_toggle'          => [ 'elType' => 'widget', 'widgetType' => 'toggle' ],
        'vc_progress_bar'    => [ 'elType' => 'widget', 'widgetType' => 'progress' ],
        'vc_cta'             => [ 'elType' => 'widget', 'widgetType' => 'button' ],
        'vc_text_separator'  => [ 'elType' => 'widget', 'widgetType' => 'heading' ],
    ];

    public function map( string $tag ): array {
        if ( isset( self::$map[ $tag ] ) ) {
            return array_merge( self::$map[ $tag ], [ 'known' => true ] );
        }

        return [
            'elType'     => 'widget',
            'widgetType' => 'html',
            'known'      => false,
        ];
    }

    public function is_container( string $tag ): bool {
        $containers = [ 'vc_row', 'vc_row_inner', 'vc_column', 'vc_column_inner' ];
        return in_array( $tag, $containers, true );
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php tests/test-mapper.php
```
Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add includes/class-mapper.php tests/test-mapper.php
git commit -m "feat: add WPBakery→Elementor mapper"
```

---

## Task 4: Converter Class

**Files:**
- Create: `wpb2elementor/includes/class-converter.php`
- Create: `wpb2elementor/tests/test-converter.php`

Walks the parsed node tree and produces a valid Elementor JSON array ready to be `json_encode()`-d into `_elementor_data`.

- [ ] **Step 1: Write failing tests**

Create `tests/test-converter.php`:

```php
<?php
require_once __DIR__ . '/../includes/class-parser.php';
require_once __DIR__ . '/../includes/class-mapper.php';
require_once __DIR__ . '/../includes/class-converter.php';

function assert_equal($a, $b, $msg = '') {
    if ($a !== $b) {
        echo "FAIL: $msg — expected " . json_encode($b) . " got " . json_encode($a) . "\n";
    } else {
        echo "PASS: $msg\n";
    }
}
function assert_not_empty($a, $msg = '') {
    if (empty($a)) { echo "FAIL: $msg is empty\n"; } else { echo "PASS: $msg\n"; }
}

$parser    = new WPB2EL_Parser();
$mapper    = new WPB2EL_Mapper();
$converter = new WPB2EL_Converter( $mapper );

// Test 1: simple heading
$nodes  = $parser->parse('[vc_row][vc_column][vc_custom_heading text="Hello"][/vc_custom_heading][/vc_column][/vc_row]');
$result = $converter->convert( $nodes );

assert_not_empty($result, 'result not empty');
assert_equal($result[0]['elType'], 'section', 'outer section');
assert_equal($result[0]['elements'][0]['elType'], 'column', 'inner column');

// Test 2: output has valid IDs
$id = $result[0]['id'];
assert_equal(strlen($id), 8, 'ID is 8 chars');

// Test 3: text-editor widget gets editor_type setting
$nodes  = $parser->parse('[vc_row][vc_column][vc_column_text]Some text[/vc_column_text][/vc_column][/vc_row]');
$result = $converter->convert( $nodes );
$widget = $result[0]['elements'][0]['elements'][0];
assert_equal($widget['widgetType'], 'text-editor', 'text widget type');
assert_equal($widget['settings']['editor'], 'Some text', 'text content in settings');

// Test 4: unknown widget becomes html placeholder
$nodes  = $parser->parse('[vc_row][vc_column][mkdf_weird_thing param="x"][/mkdf_weird_thing][/vc_column][/vc_row]');
$result = $converter->convert( $nodes );
$widget = $result[0]['elements'][0]['elements'][0];
assert_equal($widget['widgetType'], 'html', 'unknown → html');
assert_not_empty($widget['settings']['html'], 'placeholder has html');
```

- [ ] **Step 2: Run to verify fail**

```bash
php tests/test-converter.php
```

- [ ] **Step 3: Implement Converter**

Create `includes/class-converter.php`:

```php
<?php

class WPB2EL_Converter {

    private WPB2EL_Mapper $mapper;

    public function __construct( WPB2EL_Mapper $mapper ) {
        $this->mapper = $mapper;
    }

    public function convert( array $nodes ): array {
        $elements = [];
        foreach ( $nodes as $node ) {
            $el = $this->convert_node( $node );
            if ( $el ) $elements[] = $el;
        }
        return $elements;
    }

    private function convert_node( array $node ): ?array {
        if ( $node['tag'] === '__text__' ) return null;

        $mapped = $this->mapper->map( $node['tag'] );
        $id     = $this->generate_id();

        $el = [
            'id'       => $id,
            'elType'   => $mapped['elType'],
            'settings' => $this->build_settings( $node, $mapped ),
            'elements' => [],
        ];

        if ( $mapped['elType'] === 'widget' ) {
            $el['widgetType'] = $mapped['widgetType'];
        }

        // Recurse into children
        foreach ( $node['children'] as $child ) {
            $child_el = $this->convert_node( $child );
            if ( $child_el ) $el['elements'][] = $child_el;
        }

        // If no children but has text content, treat as leaf
        if ( empty( $el['elements'] ) && ! empty( $node['content'] ) ) {
            $el['settings'] = array_merge( $el['settings'], $this->build_content_settings( $node, $mapped ) );
        }

        return $el;
    }

    private function build_settings( array $node, array $mapped ): array {
        $settings = [];
        $attrs    = $node['attrs'];

        // Column width
        if ( $mapped['elType'] === 'column' && isset( $attrs['width'] ) ) {
            $settings['_column_size'] = $this->vc_width_to_percent( $attrs['width'] );
        }

        // Background color from css attribute
        if ( isset( $attrs['css'] ) ) {
            if ( preg_match( '/background(?:-color)?:\s*(#[0-9a-fA-F]{3,6}|rgba?\([^)]+\))/', $attrs['css'], $m ) ) {
                $settings['background_color'] = $m[1];
            }
        }

        // Heading text
        if ( $mapped['widgetType'] ?? '' === 'heading' && isset( $attrs['text'] ) ) {
            $settings['title'] = $attrs['text'];
        }

        // Text align
        if ( isset( $attrs['align'] ) ) {
            $settings['align'] = $attrs['align'];
        }

        return $settings;
    }

    private function build_content_settings( array $node, array $mapped ): array {
        $type = $mapped['widgetType'] ?? 'html';

        if ( $type === 'text-editor' ) {
            return [ 'editor' => $node['content'] ];
        }
        if ( $type === 'heading' ) {
            return [ 'title' => $node['content'] ];
        }
        if ( $type === 'button' ) {
            return [ 'text' => $node['content'] ];
        }
        if ( ! ( $mapped['known'] ?? true ) ) {
            // Unknown widget: wrap original shortcode as placeholder
            $shortcode = $this->rebuild_shortcode( $node );
            return [ 'html' => '<!-- WPB2EL placeholder -->' . $shortcode ];
        }

        return [ 'html' => $node['content'] ];
    }

    private function rebuild_shortcode( array $node ): string {
        $attrs = '';
        foreach ( $node['attrs'] as $k => $v ) {
            $attrs .= " {$k}=\"{$v}\"";
        }
        return "[{$node['tag']}{$attrs}]{$node['content']}[/{$node['tag']}]";
    }

    private function vc_width_to_percent( string $vc_width ): int {
        $map = [
            '1/1' => 100, '1/2' => 50, '1/3' => 33, '2/3' => 67,
            '1/4' => 25, '3/4' => 75, '1/6' => 17, '5/6' => 83,
        ];
        return $map[ $vc_width ] ?? 100;
    }

    private function generate_id(): string {
        return substr( md5( uniqid( '', true ) ), 0, 8 );
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php tests/test-converter.php
```
Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add includes/class-converter.php tests/test-converter.php
git commit -m "feat: add Elementor JSON converter"
```

---

## Task 5: Claude API Class

**Files:**
- Create: `wpb2elementor/includes/class-claude-api.php`

Sends an unknown WPBakery shortcode to Claude and returns a parsed Elementor widget array. Returns `null` on failure so the caller can fall back to HTML placeholder.

- [ ] **Step 1: Implement Claude API class**

Create `includes/class-claude-api.php`:

```php
<?php

class WPB2EL_Claude_API {

    private string $api_key;
    private string $model = 'claude-haiku-4-5-20251001';
    private string $endpoint = 'https://api.anthropic.com/v1/messages';

    public function __construct( string $api_key ) {
        $this->api_key = $api_key;
    }

    /**
     * Convert an unknown shortcode node to Elementor JSON via Claude.
     * Returns a single Elementor element array or null on failure.
     */
    public function convert_node( array $node ): ?array {
        $shortcode = $this->rebuild_shortcode( $node );

        $prompt = "You are a WordPress developer. Convert this WPBakery shortcode to a single Elementor widget JSON object.\n\n"
            . "Shortcode:\n```\n{$shortcode}\n```\n\n"
            . "Return ONLY valid JSON for a single Elementor element with this exact structure:\n"
            . "{\n"
            . "  \"elType\": \"widget\",\n"
            . "  \"widgetType\": \"<elementor_widget_type>\",\n"
            . "  \"settings\": { <relevant_settings> },\n"
            . "  \"elements\": []\n"
            . "}\n\n"
            . "If the shortcode cannot be meaningfully converted, use widgetType 'html' and put the original shortcode in settings.html.\n"
            . "Return only the JSON object, no explanation.";

        $response = wp_remote_post( $this->endpoint, [
            'timeout' => 30,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode( [
                'model'      => $this->model,
                'max_tokens' => 1024,
                'messages'   => [
                    [ 'role' => 'user', 'content' => $prompt ],
                ],
            ] ),
        ] );

        if ( is_wp_error( $response ) ) return null;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $text = $body['content'][0]['text'] ?? '';

        // Extract JSON from response
        if ( preg_match( '/\{[\s\S]+\}/', $text, $m ) ) {
            $el = json_decode( $m[0], true );
            if ( $el && isset( $el['elType'] ) ) {
                $el['id'] = substr( md5( uniqid( '', true ) ), 0, 8 );
                return $el;
            }
        }

        return null;
    }

    private function rebuild_shortcode( array $node ): string {
        $attrs = '';
        foreach ( $node['attrs'] as $k => $v ) {
            $attrs .= " {$k}=\"{$v}\"";
        }
        return "[{$node['tag']}{$attrs}]{$node['content']}[/{$node['tag']}]";
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add includes/class-claude-api.php
git commit -m "feat: add Claude API integration"
```

---

## Task 6: Prompt Export

**Files:**
- Modify: `wpb2elementor/includes/class-converter.php` (add prompt collection)
- Create: `wpb2elementor/includes/class-prompt-export.php`

When no API key is set, unknown widgets are collected and written to a text file.

- [ ] **Step 1: Create prompt export class**

Create `includes/class-prompt-export.php`:

```php
<?php

class WPB2EL_Prompt_Export {

    private array $items = [];

    public function add( string $page_title, array $node ): void {
        $attrs = '';
        foreach ( $node['attrs'] as $k => $v ) {
            $attrs .= " {$k}=\"{$v}\"";
        }
        $shortcode = "[{$node['tag']}{$attrs}]{$node['content']}[/{$node['tag']}]";

        $this->items[] = [
            'page'      => $page_title,
            'tag'       => $node['tag'],
            'shortcode' => $shortcode,
            'prompt'    => "Convert this WPBakery shortcode to an Elementor widget JSON object:\n\n```\n{$shortcode}\n```\n\nReturn JSON with elType, widgetType, settings, elements.",
        ];
    }

    public function has_items(): bool {
        return ! empty( $this->items );
    }

    public function write_file(): string {
        $path    = WP_CONTENT_DIR . '/wpb2elementor-prompts.txt';
        $content = "WPB2Elementor — Unknown Widgets\n";
        $content .= "Generated: " . date( 'Y-m-d H:i:s' ) . "\n";
        $content .= str_repeat( '=', 60 ) . "\n\n";

        foreach ( $this->items as $i => $item ) {
            $n        = $i + 1;
            $content .= "--- #{$n}: {$item['tag']} (Page: {$item['page']}) ---\n\n";
            $content .= "Paste this into claude.ai:\n\n";
            $content .= $item['prompt'] . "\n\n";
            $content .= str_repeat( '-', 40 ) . "\n\n";
        }

        file_put_contents( $path, $content );
        return $path;
    }

    public function get_items(): array {
        return $this->items;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add includes/class-prompt-export.php
git commit -m "feat: add prompt export for unknown widgets"
```

---

## Task 7: Admin UI

**Files:**
- Create: `wpb2elementor/includes/class-admin-ui.php`
- Create: `wpb2elementor/assets/admin.css`

Registers the admin page, renders the settings + page table, and handles AJAX conversion requests.

- [ ] **Step 1: Create admin CSS**

Create `assets/admin.css`:

```css
.wpb2el-wrap { max-width: 900px; }
.wpb2el-wrap .wp-list-table .status-wpbakery { color: #d63638; }
.wpb2el-wrap .wp-list-table .status-elementor { color: #1aad19; }
.wpb2el-notice { padding: 10px 15px; margin: 10px 0; border-radius: 3px; }
.wpb2el-notice.success { background: #d4edda; border-left: 4px solid #1aad19; }
.wpb2el-notice.error   { background: #f8d7da; border-left: 4px solid #d63638; }
.wpb2el-actions { display: flex; gap: 8px; align-items: center; }
```

- [ ] **Step 2: Create Admin UI class**

Create `includes/class-admin-ui.php`:

```php
<?php

class WPB2EL_Admin_UI {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_post_wpb2el_convert', [ $this, 'handle_convert' ] );
        add_action( 'admin_post_wpb2el_convert_all', [ $this, 'handle_convert_all' ] );
        add_action( 'admin_post_wpb2el_reset', [ $this, 'handle_reset' ] );
        add_action( 'admin_post_wpb2el_save_settings', [ $this, 'handle_save_settings' ] );
    }

    public function register_menu(): void {
        add_management_page(
            'WPB2Elementor',
            'WPB2Elementor',
            'manage_options',
            'wpb2elementor',
            [ $this, 'render_page' ]
        );
    }

    public function enqueue_assets( string $hook ): void {
        if ( $hook !== 'tools_page_wpb2elementor' ) return;
        wp_enqueue_style( 'wpb2el-admin', WPB2EL_URL . 'assets/admin.css', [], WPB2EL_VERSION );
    }

    public function render_page(): void {
        $pages   = $this->get_pages();
        $api_key = get_option( 'wpb2el_api_key', '' );
        $notice  = get_transient( 'wpb2el_notice' );
        if ( $notice ) delete_transient( 'wpb2el_notice' );
        ?>
        <div class="wrap wpb2el-wrap">
            <h1>WPB2Elementor</h1>

            <?php if ( $notice ) : ?>
                <div class="wpb2el-notice <?php echo esc_attr( $notice['type'] ); ?>">
                    <?php echo esc_html( $notice['message'] ); ?>
                </div>
            <?php endif; ?>

            <h2>Einstellungen</h2>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <input type="hidden" name="action" value="wpb2el_save_settings">
                <?php wp_nonce_field( 'wpb2el_settings' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Claude API Key <small>(optional)</small></th>
                        <td>
                            <input type="password" name="wpb2el_api_key"
                                   value="<?php echo esc_attr( $api_key ); ?>"
                                   class="regular-text" placeholder="sk-ant-...">
                            <p class="description">
                                Ohne Key: unbekannte Widgets → HTML-Platzhalter + Prompt-Export Datei.
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Speichern' ); ?>
            </form>

            <h2>Seiten</h2>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <input type="hidden" name="action" value="wpb2el_convert_all">
                <?php wp_nonce_field( 'wpb2el_convert_all' ); ?>
                <?php submit_button( 'Alle konvertieren', 'secondary', 'submit', false ); ?>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Seite</th>
                        <th>Status</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $pages as $page ) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_edit_post_link( $page['id'] ); ?>" target="_blank">
                                <?php echo esc_html( $page['title'] ); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ( $page['status'] === 'elementor' ) : ?>
                                <span class="status-elementor">✅ Elementor</span>
                            <?php else : ?>
                                <span class="status-wpbakery">⚠ WPBakery</span>
                            <?php endif; ?>
                        </td>
                        <td class="wpb2el-actions">
                            <?php if ( $page['status'] !== 'elementor' ) : ?>
                                <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                                    <input type="hidden" name="action" value="wpb2el_convert">
                                    <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                    <?php wp_nonce_field( 'wpb2el_convert_' . $page['id'] ); ?>
                                    <button type="submit" class="button button-primary">Konvertieren</button>
                                </form>
                            <?php endif; ?>
                            <?php if ( $page['has_backup'] ) : ?>
                                <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                                    <input type="hidden" name="action" value="wpb2el_reset">
                                    <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                    <?php wp_nonce_field( 'wpb2el_reset_' . $page['id'] ); ?>
                                    <button type="submit" class="button button-secondary"
                                            onclick="return confirm('Zurücksetzen?')">Zurücksetzen</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_save_settings(): void {
        check_admin_referer( 'wpb2el_settings' );
        update_option( 'wpb2el_api_key', sanitize_text_field( $_POST['wpb2el_api_key'] ?? '' ) );
        set_transient( 'wpb2el_notice', [ 'type' => 'success', 'message' => 'Einstellungen gespeichert.' ], 30 );
        wp_redirect( admin_url( 'tools.php?page=wpb2elementor' ) );
        exit;
    }

    public function handle_convert(): void {
        $page_id = intval( $_POST['page_id'] ?? 0 );
        check_admin_referer( 'wpb2el_convert_' . $page_id );

        $result  = $this->convert_page( $page_id );
        $notice  = $result['success']
            ? [ 'type' => 'success', 'message' => $result['message'] ]
            : [ 'type' => 'error', 'message' => $result['message'] ];

        set_transient( 'wpb2el_notice', $notice, 30 );
        wp_redirect( admin_url( 'tools.php?page=wpb2elementor' ) );
        exit;
    }

    public function handle_convert_all(): void {
        check_admin_referer( 'wpb2el_convert_all' );
        $pages   = $this->get_pages();
        $count   = 0;
        $errors  = 0;

        foreach ( $pages as $page ) {
            if ( $page['status'] === 'elementor' ) continue;
            $result = $this->convert_page( $page['id'] );
            $result['success'] ? $count++ : $errors++;
        }

        $msg = "Konvertiert: {$count} Seiten.";
        if ( $errors ) $msg .= " Fehler: {$errors}.";

        set_transient( 'wpb2el_notice', [ 'type' => 'success', 'message' => $msg ], 30 );
        wp_redirect( admin_url( 'tools.php?page=wpb2elementor' ) );
        exit;
    }

    public function handle_reset(): void {
        $page_id = intval( $_POST['page_id'] ?? 0 );
        check_admin_referer( 'wpb2el_reset_' . $page_id );

        $backup = get_post_meta( $page_id, '_wpb2el_backup', true );
        if ( ! $backup ) {
            set_transient( 'wpb2el_notice', [ 'type' => 'error', 'message' => 'Kein Backup gefunden.' ], 30 );
        } else {
            wp_update_post( [ 'ID' => $page_id, 'post_content' => $backup ] );
            delete_post_meta( $page_id, '_elementor_data' );
            delete_post_meta( $page_id, '_elementor_edit_mode' );
            set_transient( 'wpb2el_notice', [ 'type' => 'success', 'message' => 'Seite zurückgesetzt.' ], 30 );
        }

        wp_redirect( admin_url( 'tools.php?page=wpb2elementor' ) );
        exit;
    }

    private function convert_page( int $page_id ): array {
        $post = get_post( $page_id );
        if ( ! $post ) return [ 'success' => false, 'message' => "Seite #{$page_id} nicht gefunden." ];

        $content = $post->post_content;
        if ( empty( trim( $content ) ) ) {
            return [ 'success' => false, 'message' => "{$post->post_title}: leerer Inhalt." ];
        }

        // Backup original content
        update_post_meta( $page_id, '_wpb2el_backup', $content );

        $parser    = new WPB2EL_Parser();
        $mapper    = new WPB2EL_Mapper();
        $converter = new WPB2EL_Converter( $mapper );
        $api_key   = get_option( 'wpb2el_api_key', '' );
        $claude    = $api_key ? new WPB2EL_Claude_API( $api_key ) : null;
        $exporter  = new WPB2EL_Prompt_Export();

        try {
            $nodes    = $parser->parse( $content );
            $elements = $converter->convert( $nodes, $claude, $exporter, $post->post_title );

            update_post_meta( $page_id, '_elementor_data', wp_slash( json_encode( $elements ) ) );
            update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
            update_post_meta( $page_id, '_elementor_version', '3.0.0' );
            wp_update_post( [ 'ID' => $page_id, 'post_content' => '' ] );

            $msg = "✅ {$post->post_title} konvertiert.";
            if ( $exporter->has_items() ) {
                $path = $exporter->write_file();
                $msg .= " Unbekannte Widgets → Prompt-Datei gespeichert.";
            }

            return [ 'success' => true, 'message' => $msg ];
        } catch ( \Throwable $e ) {
            return [ 'success' => false, 'message' => "Fehler: " . $e->getMessage() ];
        }
    }

    private function get_pages(): array {
        $wc_pages = array_filter( [
            get_option( 'woocommerce_shop_page_id' ),
            get_option( 'woocommerce_cart_page_id' ),
            get_option( 'woocommerce_checkout_page_id' ),
            get_option( 'woocommerce_myaccount_page_id' ),
        ] );

        $posts  = get_posts( [ 'post_type' => 'page', 'numberposts' => -1, 'post_status' => 'any' ] );
        $result = [];

        foreach ( $posts as $post ) {
            if ( in_array( $post->ID, $wc_pages, false ) ) continue;

            $edit_mode  = get_post_meta( $post->ID, '_elementor_edit_mode', true );
            $has_backup = (bool) get_post_meta( $post->ID, '_wpb2el_backup', true );

            $result[] = [
                'id'         => $post->ID,
                'title'      => $post->post_title,
                'status'     => $edit_mode === 'builder' ? 'elementor' : 'wpbakery',
                'has_backup' => $has_backup,
            ];
        }

        return $result;
    }
}
```

- [ ] **Step 3: Update Converter to accept Claude + Exporter**

Modify `includes/class-converter.php` — update `convert()` signature:

```php
public function convert( array $nodes, ?WPB2EL_Claude_API $claude = null, ?WPB2EL_Prompt_Export $exporter = null, string $page_title = '' ): array {
    $this->claude     = $claude;
    $this->exporter   = $exporter;
    $this->page_title = $page_title;
    $elements = [];
    foreach ( $nodes as $node ) {
        $el = $this->convert_node( $node );
        if ( $el ) $elements[] = $el;
    }
    return $elements;
}
```

Add properties to the class:
```php
private ?WPB2EL_Claude_API $claude = null;
private ?WPB2EL_Prompt_Export $exporter = null;
private string $page_title = '';
```

Update `convert_node()` — for unknown widgets:
```php
if ( ! $mapped['known'] ) {
    // Try Claude API first
    if ( $this->claude ) {
        $claude_el = $this->claude->convert_node( $node );
        if ( $claude_el ) {
            $claude_el['id'] = $this->generate_id();
            return $claude_el;
        }
    }
    // Fall back to prompt export + HTML placeholder
    if ( $this->exporter ) {
        $this->exporter->add( $this->page_title, $node );
    }
    $shortcode = $this->rebuild_shortcode( $node );
    return [
        'id'         => $this->generate_id(),
        'elType'     => 'widget',
        'widgetType' => 'html',
        'settings'   => [ 'html' => '<!-- WPB2EL unknown: ' . $node['tag'] . ' -->' . $shortcode ],
        'elements'   => [],
    ];
}
```

- [ ] **Step 4: Commit**

```bash
git add includes/class-admin-ui.php includes/class-prompt-export.php includes/class-converter.php assets/admin.css
git commit -m "feat: add admin UI, prompt export, and Claude API wiring"
```

---

## Task 8: Install & Test in Local by Flywheel

- [ ] **Step 1: Copy plugin to new WordPress instance**

```bash
# Adjust path to your Local by Flywheel site
cp -r ~/Desktop/wpb2elementor "/Users/alexander/Local Sites/tubeampmanufactur-new/app/public/wp-content/plugins/"
```

- [ ] **Step 2: Activate plugin via WP-CLI**

Open Local Shell for the new instance (10038):

```bash
wp plugin activate wpb2elementor
```

Expected: `Plugin 'wpb2elementor' activated.`

- [ ] **Step 3: Open Admin UI**

Go to: `http://localhost:10038/wp-admin/tools.php?page=wpb2elementor`

Verify: page list shows all pages with ⚠ WPBakery status.

- [ ] **Step 4: Convert one test page**

Click "Konvertieren" on "Über Uns" (ID 883).

Then verify in Elementor: open Über Uns in Elementor editor → should show converted content.

- [ ] **Step 5: Test reset**

Click "Zurücksetzen" on the same page → verify original WPBakery content is restored.

- [ ] **Step 6: Push to GitHub**

```bash
cd ~/Desktop/wpb2elementor
git push origin main
```

---

## Self-Review Checklist

- [x] Parser → Mapper → Converter → ClaudeAPI → AdminUI data flow is complete
- [x] Backup before every conversion
- [x] WooCommerce pages excluded from list
- [x] 3 modes covered: static mapping, Claude API, prompt export
- [x] Reset functionality implemented
- [x] All unknown widgets handled gracefully (no data loss)
- [x] WPML not in scope (noted in spec)
- [x] RevSlider not in scope (noted in spec)
