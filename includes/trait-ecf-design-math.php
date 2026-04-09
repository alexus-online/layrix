<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Design_Math_Trait {
    private function derived_base_body_text_size($settings = null) {
        if (!is_array($settings)) {
            $settings = $this->get_settings();
        }

        $scale = is_array($settings['typography']['scale'] ?? null)
            ? $settings['typography']['scale']
            : [];
        $steps = is_array($scale['steps'] ?? null) ? $scale['steps'] : ['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl'];
        $base_index = sanitize_key($scale['base_index'] ?? 'm');
        if ($base_index === '' || !in_array($base_index, $steps, true)) {
            $base_index = in_array('m', $steps, true) ? 'm' : (string) reset($steps);
        }

        $root_base_px = $this->get_root_font_base_px($settings);
        foreach ($this->build_type_scale_preview($scale + ['steps' => $steps, 'base_index' => $base_index], $root_base_px) as $item) {
            if (($item['step'] ?? '') === $base_index && ($item['max_px'] ?? '') !== '') {
                return trim((string) $item['max_px']) . 'px';
            }
        }

        $max_base = (float) ($scale['max_base'] ?? $scale['base'] ?? 16);
        return $this->format_preview_number($max_base, 3) . 'px';
    }

    private function should_upgrade_base_body_text_size($value, $settings = null) {
        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['', '1rem', '16px', '16rem'], true)) {
            return true;
        }

        if (!is_array($settings)) {
            return false;
        }

        $parts = $this->parse_css_size_parts($normalized);
        $format = strtolower((string) ($parts['format'] ?? ''));
        $numeric = (float) str_replace(',', '.', (string) ($parts['value'] ?? '0'));
        $scale = is_array($settings['typography']['scale'] ?? null) ? $settings['typography']['scale'] : [];
        $max_base = (float) ($scale['max_base'] ?? $scale['base'] ?? 16);
        $root_base_px = $this->get_root_font_base_px($settings);
        $legacy_rem = (float) $this->format_preview_number($this->format_rem_value($max_base, 2, $root_base_px));

        if ($format === 'rem' && abs($numeric - $max_base) < 0.0001) {
            return true;
        }

        if ($format === 'rem' && abs($numeric - $legacy_rem) < 0.0001) {
            return true;
        }

        return false;
    }

    private function base_body_text_size_warning_message($value, $settings = null) {
        $parts = $this->parse_css_size_parts($value);
        $format = strtolower((string) ($parts['format'] ?? ''));
        $numeric = (float) str_replace(',', '.', (string) ($parts['value'] ?? '0'));

        if ($numeric <= 0 || $format === 'custom') {
            return '';
        }

        $root_base_px = $this->get_root_font_base_px($settings);
        $px_equivalent = null;

        if ($format === 'px') {
            $px_equivalent = $numeric;
        } elseif (in_array($format, ['rem', 'em'], true)) {
            $px_equivalent = $numeric * $root_base_px;
        }

        if (in_array($format, ['rem', 'em'], true) && $numeric >= 8) {
            return __('This value looks unusually large for rem/em body text. Did you mean px or the value from your active type scale token?', 'ecf-framework');
        }

        if ($px_equivalent !== null && ($px_equivalent < 10 || $px_equivalent > 32)) {
            return __('This body text size looks unusual for normal reading text. Please double-check the unit and value.', 'ecf-framework');
        }

        return '';
    }

    private function get_root_font_base_px($settings = null) {
        if (is_array($settings)) {
            $value = str_replace(',', '.', (string) ($settings['root_font_size'] ?? ''));
        } else {
            $current = $this->get_settings();
            $value = str_replace(',', '.', (string) ($current['root_font_size'] ?? ''));
        }

        return $value === '62.5' ? 10 : 16;
    }

    private function get_root_font_css_value($settings = null) {
        if (is_array($settings)) {
            $value = str_replace(',', '.', (string) ($settings['root_font_size'] ?? ''));
        } else {
            $current = $this->get_settings();
            $value = str_replace(',', '.', (string) ($current['root_font_size'] ?? ''));
        }

        return $value === '62.5' ? '62.5%' : '100%';
    }

    private function build_spacing_scale($settings, $root_base_px = 16) {
        $steps      = $settings['steps'];
        $max_base   = floatval($settings['max_base'] ?? $settings['base'] ?? 16);
        $min_base   = floatval($settings['min_base'] ?? $max_base * ($settings['scale_factor'] ?? 0.75));
        $max_ratio  = floatval($settings['max_ratio'] ?? $settings['ratio_up'] ?? 1.25);
        $min_ratio  = floatval($settings['min_ratio'] ?? $settings['ratio_up'] ?? 1.2);
        $base_index = array_search($settings['base_index'], $steps, true);
        if ($base_index === false) $base_index = (int)(count($steps) / 2);
        $fluid      = !empty($settings['fluid']);
        $min_vw     = intval($settings['min_vw'] ?? 375);
        $max_vw     = intval($settings['max_vw'] ?? 1280);
        $scale = [];
        foreach ($steps as $i => $step) {
            $exp = $i - $base_index;
            if ($exp === 0) {
                $max_size = $max_base;
                $min_size = $min_base;
            } elseif ($exp > 0) {
                $max_size = $max_base * pow($max_ratio, $exp);
                $min_size = $min_base * pow($min_ratio, $exp);
            } else {
                $max_size = $max_base / pow($max_ratio, abs($exp));
                $min_size = $min_base / pow($min_ratio, abs($exp));
            }
            $max_size = round($max_size, 3);
            $min_size = round($min_size, 3);
            if ($min_size > $max_size) {
                $swap = $min_size;
                $min_size = $max_size;
                $max_size = $swap;
            }
            if ($fluid && $max_vw > $min_vw) {
                $scale[$step] = $this->build_fluid_rem_clamp($min_size, $max_size, $min_vw, $max_vw, $root_base_px);
            } else {
                $scale[$step] = $this->format_preview_number($this->format_rem_value($max_size, 2, $root_base_px)) . 'rem';
            }
        }
        return $scale;
    }

    private function build_spacing_scale_preview($settings, $root_base_px = 16) {
        $steps      = $settings['steps'];
        $max_base   = floatval($settings['max_base'] ?? $settings['base'] ?? 16);
        $min_base   = floatval($settings['min_base'] ?? $max_base * ($settings['scale_factor'] ?? 0.75));
        $max_ratio  = floatval($settings['max_ratio'] ?? $settings['ratio_up'] ?? 1.25);
        $min_ratio  = floatval($settings['min_ratio'] ?? $settings['ratio_up'] ?? 1.2);
        $base_index = array_search($settings['base_index'], $steps, true);
        if ($base_index === false) $base_index = (int)(count($steps) / 2);
        $fluid   = !empty($settings['fluid']);
        $min_vw  = intval($settings['min_vw'] ?? 375);
        $max_vw  = intval($settings['max_vw'] ?? 1280);
        $prefix  = sanitize_key($settings['prefix'] ?? 'space');
        $items = [];
        foreach ($steps as $i => $step) {
            $exp = $i - $base_index;
            if ($exp === 0) {
                $max_size = $max_base;
                $min_size = $min_base;
            } elseif ($exp > 0) {
                $max_size = $max_base * pow($max_ratio, $exp);
                $min_size = $min_base * pow($min_ratio, $exp);
            } else {
                $max_size = $max_base / pow($max_ratio, abs($exp));
                $min_size = $min_base / pow($min_ratio, abs($exp));
            }
            $max_size = round($max_size, 3);
            $min_size = round($min_size, 3);
            if ($min_size > $max_size) {
                $swap = $min_size;
                $min_size = $max_size;
                $max_size = $swap;
            }
            if ($fluid && $max_vw > $min_vw) {
                $css_value = $this->build_fluid_rem_clamp($min_size, $max_size, $min_vw, $max_vw, $root_base_px);
            } else {
                $css_value = $this->format_preview_number($this->format_rem_value($max_size, 2, $root_base_px)) . 'rem';
                $min_size  = $max_size;
            }
            $items[] = [
                'step'      => $step,
                'token'     => "--ecf-{$prefix}-{$step}",
                'min'       => $this->format_preview_number($this->format_rem_value($min_size, 2, $root_base_px)),
                'max'       => $this->format_preview_number($this->format_rem_value($max_size, 2, $root_base_px)),
                'min_px'    => $this->format_preview_number($min_size, 3),
                'max_px'    => $this->format_preview_number($max_size, 3),
                'css_value' => $css_value,
                'is_base'   => ($i === $base_index),
            ];
        }
        return $items;
    }

    private function radius_css_value($row, $min_vw = 375, $max_vw = 1280, $root_base_px = 16) {
        $min_val = trim($row['min'] ?? $row['value'] ?? '');
        $max_val = trim($row['max'] ?? $row['value'] ?? '');
        if ($min_val === $max_val) return $max_val;
        $min_px = floatval($min_val);
        $max_px = floatval($max_val);
        if ($min_px <= 0 || $max_px <= 0 || $max_vw <= $min_vw) return $max_val;
        return $this->build_fluid_rem_clamp($min_px, $max_px, $min_vw, $max_vw, $root_base_px);
    }

    private function format_rem_value($px, $precision = 2, $root_base_px = 16) {
        return round(floatval($px) / max(1, floatval($root_base_px)), $precision);
    }

    private function build_fluid_rem_clamp($min_size, $max_size, $min_vw, $max_vw, $root_base_px = 16) {
        $slope = (floatval($max_size) - floatval($min_size)) / (floatval($max_vw) - floatval($min_vw));
        $slope_vw = round($slope * 100, 2);
        $intercept_px = floatval($min_size) - $slope * floatval($min_vw);
        $intercept_rem = round($intercept_px / max(1, floatval($root_base_px)), 2);
        $operator = $intercept_rem >= 0 ? '+' : '-';

        return sprintf(
            'clamp(%srem, calc(%svw %s %srem), %srem)',
            $this->format_preview_number($this->format_rem_value($min_size, 2, $root_base_px)),
            $this->format_preview_number($slope_vw),
            $operator,
            $this->format_preview_number(abs($intercept_rem)),
            $this->format_preview_number($this->format_rem_value($max_size, 2, $root_base_px))
        );
    }

    private function build_type_scale($scale, $root_base_px = 16) {
        $steps      = $scale['steps'];
        $min_base   = floatval($scale['min_base'] ?? ($scale['max_base'] ?? 16) * floatval($scale['scale_factor'] ?? 0.8));
        $max_base   = floatval($scale['max_base'] ?? $scale['base'] ?? 16);
        $min_ratio  = floatval($scale['min_ratio'] ?? $scale['ratio'] ?? 1.125);
        $max_ratio  = floatval($scale['max_ratio'] ?? $scale['ratio'] ?? 1.25);
        $base_index = array_search($scale['base_index'], $steps, true);
        if ($base_index === false) $base_index = 2;
        $fluid        = !empty($scale['fluid']);
        $min_vw       = intval($scale['min_vw']);
        $max_vw       = intval($scale['max_vw']);

        $result = [];
        foreach ($steps as $i => $step) {
            $exp      = $i - $base_index;
            $max_size = round($max_base * pow($max_ratio, $exp), 3);
            $min_size = round($min_base * pow($min_ratio, $exp), 3);
            if ($min_size > $max_size) { [$min_size, $max_size] = [$max_size, $min_size]; }

            if ($fluid && $max_vw > $min_vw) {
                $result[$step] = $this->build_fluid_rem_clamp($min_size, $max_size, $min_vw, $max_vw, $root_base_px);
            } else {
                $result[$step] = $this->format_preview_number($this->format_rem_value($max_size, 2, $root_base_px)) . 'rem';
            }
        }
        return $result;
    }

    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', trim($hex));
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        if (strlen($hex) !== 6) return [59,130,246];
        return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
    }

    private function detect_color_format($value) {
        $value = strtolower(trim((string) $value));
        if ($value === '') return 'hex';
        if (preg_match('/^#[0-9a-f]{8}$/', $value)) return 'hexa';
        if (preg_match('/^#[0-9a-f]{6}$/', $value)) return 'hex';
        if (strpos($value, 'rgba(') === 0) return 'rgba';
        if (strpos($value, 'rgb(') === 0) return 'rgb';
        if (strpos($value, 'hsla(') === 0) return 'hsla';
        if (strpos($value, 'hsl(') === 0) return 'hsl';
        return 'hex';
    }

    private function normalize_alpha($alpha) {
        return max(0, min(1, (float) $alpha));
    }

    private function alpha_to_hex($alpha) {
        return str_pad(dechex((int) round($this->normalize_alpha($alpha) * 255)), 2, '0', STR_PAD_LEFT);
    }

    private function parse_css_color($value) {
        $value = trim((string) $value);
        if ($value === '') return null;

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value, $m)) {
            $hex = strtolower($m[1]);
            if (strlen($hex) === 3 || strlen($hex) === 4) {
                $hex = preg_replace('/(.)/', '$1$1', $hex);
            }
            $alpha = 1;
            if (strlen($hex) === 8) {
                $alpha = hexdec(substr($hex, 6, 2)) / 255;
                $hex = substr($hex, 0, 6);
            }
            return [
                'r' => hexdec(substr($hex, 0, 2)),
                'g' => hexdec(substr($hex, 2, 2)),
                'b' => hexdec(substr($hex, 4, 2)),
                'a' => $this->normalize_alpha($alpha),
            ];
        }

        if (preg_match('/^rgba?\(\s*([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)(?:\s*,\s*([+-]?\d*(?:\.\d+)?))?\s*\)$/i', $value, $m)) {
            return [
                'r' => max(0, min(255, (float) $m[1])),
                'g' => max(0, min(255, (float) $m[2])),
                'b' => max(0, min(255, (float) $m[3])),
                'a' => isset($m[4]) && $m[4] !== '' ? $this->normalize_alpha($m[4]) : 1,
            ];
        }

        if (preg_match('/^hsla?\(\s*([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)%\s*,\s*([+-]?\d+(?:\.\d+)?)%(?:\s*,\s*([+-]?\d*(?:\.\d+)?))?\s*\)$/i', $value, $m)) {
            $h = fmod((float) $m[1], 360.0);
            if ($h < 0) $h += 360.0;
            $s = max(0, min(100, (float) $m[2])) / 100;
            $l = max(0, min(100, (float) $m[3])) / 100;
            $rgb = $this->hsl_to_rgb($h / 360, $s, $l);
            return [
                'r' => round($rgb['r'] * 255),
                'g' => round($rgb['g'] * 255),
                'b' => round($rgb['b'] * 255),
                'a' => isset($m[4]) && $m[4] !== '' ? $this->normalize_alpha($m[4]) : 1,
            ];
        }

        return null;
    }

    private function format_css_color($color, $format = 'hex') {
        if (!is_array($color) || !isset($color['r'], $color['g'], $color['b'])) return '';
        $format = strtolower((string) $format);
        $allowed = ['hex', 'hexa', 'rgb', 'rgba', 'hsl', 'hsla'];
        if (!in_array($format, $allowed, true)) $format = 'hex';

        $r = (int) round(max(0, min(255, $color['r'])));
        $g = (int) round(max(0, min(255, $color['g'])));
        $b = (int) round(max(0, min(255, $color['b'])));
        $a = $this->normalize_alpha($color['a'] ?? 1);
        $hsl = $this->rgb_to_hsl($r / 255, $g / 255, $b / 255);
        $h = round($hsl['h'] * 360, 1);
        $s = round($hsl['s'] * 100, 1);
        $l = round($hsl['l'] * 100, 1);
        $alpha = rtrim(rtrim(number_format($a, 3, '.', ''), '0'), '.');
        if ($alpha === '') $alpha = '0';

        if ($format === 'rgb') return sprintf('rgb(%d, %d, %d)', $r, $g, $b);
        if ($format === 'rgba') return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, $alpha);
        if ($format === 'hsl') return sprintf('hsl(%s, %s%%, %s%%)', $this->trim_number($h), $this->trim_number($s), $this->trim_number($l));
        if ($format === 'hsla') return sprintf('hsla(%s, %s%%, %s%%, %s)', $this->trim_number($h), $this->trim_number($s), $this->trim_number($l), $alpha);
        if ($format === 'hexa') return sprintf('#%02X%02X%02X%s', $r, $g, $b, strtoupper($this->alpha_to_hex($a)));
        if ($a < 1) return sprintf('#%02X%02X%02X%s', $r, $g, $b, strtoupper($this->alpha_to_hex($a)));
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    private function trim_number($value) {
        $value = round((float) $value, 1);
        $str = number_format($value, 1, '.', '');
        return preg_replace('/\.0$/', '', $str);
    }

    private function sanitize_css_color_value($value, $format = '') {
        $parsed = $this->parse_css_color($value);
        if (!$parsed) return '';
        $format = strtolower((string) $format);
        if ($format === '') $format = $this->detect_color_format($value);
        if (($parsed['a'] ?? 1) < 1) {
            if ($format === 'hex') $format = 'hexa';
            if ($format === 'rgb') $format = 'rgba';
            if ($format === 'hsl') $format = 'hsla';
        }
        return $this->format_css_color($parsed, $format);
    }

    private function rgb_to_hsl($r,$g,$b) {
        $max = max($r,$g,$b); $min = min($r,$g,$b);
        $h=0; $s=0; $l=($max+$min)/2;
        if ($max !== $min) {
            $d = $max-$min;
            $s = $l > 0.5 ? $d/(2-$max-$min) : $d/($max+$min);
            if ($max === $r) $h = ($g-$b)/$d + ($g < $b ? 6 : 0);
            elseif ($max === $g) $h = ($b-$r)/$d + 2;
            else $h = ($r-$g)/$d + 4;
            $h /= 6;
        }
        return ['h'=>$h,'s'=>$s,'l'=>$l];
    }

    private function hue_to_rgb($p,$q,$t) {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q-$p)*6*$t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q-$p)*(2/3-$t)*6;
        return $p;
    }

    private function hsl_to_rgb($h,$s,$l) {
        if ($s == 0) return ['r'=>$l,'g'=>$l,'b'=>$l];
        $q = $l < 0.5 ? $l*(1+$s) : $l+$s-$l*$s;
        $p = 2*$l-$q;
        return [
            'r' => $this->hue_to_rgb($p,$q,$h+1/3),
            'g' => $this->hue_to_rgb($p,$q,$h),
            'b' => $this->hue_to_rgb($p,$q,$h-1/3),
        ];
    }

    private function shades_for_hex($hex) {
        $parsed = $this->parse_css_color($hex);
        $rgb = $parsed ? [$parsed['r'], $parsed['g'], $parsed['b']] : $this->hex_to_rgb($hex);
        $alpha = $parsed ? $this->normalize_alpha($parsed['a'] ?? 1) : 1;
        $hsl = $this->rgb_to_hsl($rgb[0]/255, $rgb[1]/255, $rgb[2]/255);
        $map = [50=>0.95,100=>0.9,200=>0.8,300=>0.7,400=>0.6,500=>$hsl['l'],600=>0.45,700=>0.35,800=>0.25,900=>0.16];
        $out = [];
        foreach ($map as $key=>$lightness) {
            $rgb2 = $this->hsl_to_rgb($hsl['h'], $hsl['s'], $lightness);
            $shade = [
                'r' => round($rgb2['r']*255),
                'g' => round($rgb2['g']*255),
                'b' => round($rgb2['b']*255),
                'a' => $alpha,
            ];
            $out[$key] = $this->format_css_color($shade, $alpha < 1 ? 'rgba' : 'hex');
        }
        return $out;
    }
}
