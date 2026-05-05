<?php
/**
 * Trait: v2 UI view — parallel HTML rendered alongside v1, shown when [data-ecf-ui-v2] is active.
 * All pages are wrapped in #ecf-v2-form; ecfV2Save() collects inputs and calls the REST API.
 */
trait ECF_Framework_Admin_V2_View_Trait {

    private function render_v2_wrapper( $settings ) {
        $ver = get_plugin_data( ECF_FRAMEWORK_FILE )['Version'] ?? '0.4';
        $opt = $this->option_name; // ecf_framework_v50

        /* ── Color data (indexed array) ─────────────────────────────── */
        $colors_arr = $settings['colors'] ?? [];
        $c = [];   // [name => hex]
        $c_idx = []; // [name => index]
        foreach ( $colors_arr as $i => $row ) {
            $name = sanitize_key( $row['name'] ?? '' );
            if ( $name ) {
                $parsed = $this->parse_css_color( $row['value'] ?? '#000000' );
                $hex    = $this->format_css_color( $parsed, 'hex' );
                $c[ $name ]     = $hex ?: '#000000';
                $c_idx[ $name ] = $i;
            }
        }
        $shade_data = []; // [name => ['shades' => [...], 'tints' => [...]]]
        foreach ( $colors_arr as $i => $row ) {
            $name = sanitize_key( $row['name'] ?? '' );
            if ( ! $name || ! isset( $c[ $name ] ) ) continue;
            $hex        = $c[ $name ];
            $cnt_sh     = min( 10, max( 1, (int) ( $row['shade_count'] ?? 6 ) ) );
            $cnt_ti     = min( 10, max( 1, (int) ( $row['tint_count']  ?? 6 ) ) );
            $shade_data[ $name ] = [
                'shades' => ! empty( $row['generate_shades'] ) ? $this->v2_generate_darkshades( $hex, $cnt_sh ) : [],
                'tints'  => ! empty( $row['generate_tints']  ) ? $this->v2_generate_shades( $hex, $cnt_ti )    : [],
            ];
        }

        /* ── Radius data ─────────────────────────────────────────────── */
        $radius_arr = $settings['radius'] ?? [];
        $radius_css = [];
        foreach ( $radius_arr as $row ) {
            $rname = sanitize_key( $row['name'] ?? '' );
            if ( $rname ) $radius_css[ $rname ] = $row['min'] ?? '4px';
        }

        /* ── Shadow data ─────────────────────────────────────────────── */
        $shadows_arr = is_array( $settings['shadows'] ?? null )
            ? array_values( $settings['shadows'] )
            : [];
        $sh = [];
        foreach ( $shadows_arr as $row ) {
            $sname = sanitize_key( $row['name'] ?? '' );
            if ( $sname ) $sh[ $sname ] = $row['value'] ?? '';
        }
        if ( empty( $sh ) ) {
            $sh = [
                'xs'    => '0 1px 2px rgba(0,0,0,.07), 0 1px 4px rgba(0,0,0,.04)',
                's'     => '0 2px 4px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.06)',
                'm'     => '0 4px 8px rgba(0,0,0,.09), 0 8px 24px rgba(0,0,0,.08)',
                'l'     => '0 8px 16px rgba(0,0,0,.10), 0 16px 40px rgba(0,0,0,.10)',
                'xl'    => '0 16px 32px rgba(0,0,0,.12), 0 32px 64px rgba(0,0,0,.14)',
                'inner' => 'inset 0 2px 4px rgba(0,0,0,.06)',
            ];
        }

        /* ── Typography data ─────────────────────────────────────────── */
        $fonts_arr     = $settings['typography']['fonts'] ?? [];
        $typo_scale    = $settings['typography']['scale'] ?? [];
        $font_primary  = 'Inter, system-ui, sans-serif';
        $font_secondary = 'Georgia, serif';
        foreach ( $fonts_arr as $row ) {
            if ( ( $row['name'] ?? '' ) === 'primary' )   $font_primary   = $row['value'] ?? $font_primary;
            if ( ( $row['name'] ?? '' ) === 'secondary' ) $font_secondary = $row['value'] ?? $font_secondary;
        }

        /* ── Spacing data ────────────────────────────────────────────── */
        $spacing     = $settings['spacing'] ?? [];
        $sp_base_min  = $spacing['min_base']  ?? '16';
        $sp_base_max  = $spacing['max_base']  ?? '28';
        $sp_ratio_min = $spacing['min_ratio'] ?? '1.25';
        $sp_ratio_max = $spacing['max_ratio'] ?? '1.414';

        /* ── General settings ────────────────────────────────────────── */
        $general      = $settings['general'] ?? [];
        $root_size    = $general['root_font_size'] ?? $settings['root_font_size'] ?? '62.5';
        $container_w  = isset( $general['elementor_boxed_width'] )
            ? ( $general['elementor_boxed_width']['value'] ?? $general['elementor_boxed_width'] ?? '1140' ) . ( is_array( $general['elementor_boxed_width'] ) ? ( $general['elementor_boxed_width']['format'] ?? 'px' ) : '' )
            : ( $settings['elementor_boxed_width'] ?? '1140px' );
        $text_max_w   = isset( $general['content_max_width'] )
            ? ( $general['content_max_width']['value'] ?? $general['content_max_width'] ?? '72' ) . ( is_array( $general['content_max_width'] ) ? ( $general['content_max_width']['format'] ?? 'ch' ) : '' )
            : ( $settings['content_max_width'] ?? '72ch' );

        /* ── Variable counts (dynamic) ───────────────────────────────── */
        $var_count_colors = 0;
        foreach ( $colors_arr as $row ) {
            $var_count_colors++;
            if ( ! empty( $row['generate_shades'] ) ) $var_count_colors += (int) ( $row['shade_count'] ?? 6 );
            if ( ! empty( $row['generate_tints'] ) )  $var_count_colors += (int) ( $row['tint_count'] ?? 6 );
        }
        $var_count_radius  = count( $radius_arr );
        $var_count_shadows = count( $shadows_arr );
        $typo_steps        = count( $typo_scale['steps'] ?? [ 'xs','s','m','l','xl','2xl','3xl','4xl' ] );
        $spacing_steps     = count( $spacing['steps'] ?? [ '3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl' ] );

        /* ── Scale overview px values for aside ─────────────────────────── */
        $scale_steps = [];
        try {
            $root_base_px = $this->get_root_font_base_px( $settings );
            $scale_for_calc = array_merge( [
                'steps'      => [ 'xs','s','m','l','xl','2xl','3xl','4xl' ],
                'min_base'   => 16,
                'max_base'   => 18,
                'min_ratio'  => 1.125,
                'max_ratio'  => 1.25,
                'base_index' => 'm',
                'fluid'      => true,
                'min_vw'     => 375,
                'max_vw'     => 1280,
            ], $typo_scale );
            if ( empty( $scale_for_calc['steps'] ) ) {
                $scale_for_calc['steps'] = [ 'xs','s','m','l','xl','2xl','3xl','4xl' ];
            }
            $raw_scale = $this->build_type_scale( $scale_for_calc, $root_base_px );
            foreach ( $raw_scale as $step => $css_value ) {
                /* clamp(Xrem, ...) — fluid output: first value is min size in rem */
                if ( preg_match( '/clamp\(\s*([\d.]+)rem/', $css_value, $m ) ) {
                    $scale_steps[ $step ] = round( (float) $m[1] * $root_base_px );
                /* last fallback: plain Xrem */
                } elseif ( preg_match( '/([\d.]+)rem/', $css_value, $m ) ) {
                    $scale_steps[ $step ] = round( (float) $m[1] * $root_base_px );
                /* static px output (fluid=false) */
                } elseif ( preg_match( '/([\d.]+)px/', $css_value, $m ) ) {
                    $scale_steps[ $step ] = round( (float) $m[1] );
                }
            }
        } catch ( \Throwable $e ) {
            $scale_steps = [];
        }
        $var_count_total   = $var_count_colors + $var_count_radius + $var_count_shadows + $typo_steps + $spacing_steps;

        /* ── Classes data ────────────────────────────────────────────── */
        $starter_lib      = $this->starter_class_library();
        $utility_lib      = $this->utility_class_library();
        $enabled_map      = $settings['starter_classes']['enabled'] ?? [];
        $utility_enabled  = $settings['utility_classes']['enabled'] ?? [];
        $custom_classes   = $settings['starter_classes']['custom'] ?? [];
        $active_starter_basic = 0;
        foreach ( $starter_lib['basic'] ?? [] as $cls ) {
            if ( ! empty( $enabled_map[ $cls['name'] ] ) ) $active_starter_basic++;
        }
        $active_starter_extra = 0;
        foreach ( $starter_lib['advanced'] ?? [] as $cls ) {
            if ( ! empty( $enabled_map[ $cls['name'] ] ) ) $active_starter_extra++;
        }
        $active_starter = $active_starter_basic + $active_starter_extra;
        $active_utility = 0;
        foreach ( $utility_lib as $group ) {
            foreach ( $group as $cls ) {
                if ( ! empty( $utility_enabled[ $cls['name'] ] ) ) $active_utility++;
            }
        }
        $active_custom = 0;
        foreach ( $custom_classes as $row ) {
            if ( trim( (string) ( $row['name'] ?? '' ) ) !== '' ) $active_custom++;
        }
        $active_total = $active_starter + $active_utility + $active_custom;

        /* ── Sync preview data ───────────────────────────────────────── */
        $sync_var_count = $var_count_total;
        $sync_cls_count = $active_total;

        /* ── Export URL / nonce ──────────────────────────────────────── */
        $export_url = admin_url( 'admin-post.php' );
        $generated_css          = $this->build_generated_css( $settings, true );
        $generated_css_download = 'data:text/css;charset=utf-8,' . rawurlencode( $generated_css );

        /* ── Style presets ───────────────────────────────────────────── */
        $sp_shadows = ['xs'=>'0 1px 2px rgba(0,0,0,.07), 0 1px 4px rgba(0,0,0,.04)','s'=>'0 2px 4px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.06)','m'=>'0 4px 8px rgba(0,0,0,.09), 0 8px 24px rgba(0,0,0,.08)','l'=>'0 8px 16px rgba(0,0,0,.10), 0 16px 40px rgba(0,0,0,.10)','xl'=>'0 16px 32px rgba(0,0,0,.12), 0 32px 64px rgba(0,0,0,.14)','inner'=>'inset 0 2px 4px rgba(0,0,0,.06)'];
        $sp_shadows_soft = ['xs'=>'0 1px 3px rgba(0,0,0,.05)','s'=>'0 2px 6px rgba(0,0,0,.07), 0 4px 12px rgba(0,0,0,.05)','m'=>'0 4px 12px rgba(0,0,0,.08), 0 8px 24px rgba(0,0,0,.06)','l'=>'0 8px 20px rgba(0,0,0,.09), 0 16px 40px rgba(0,0,0,.08)','xl'=>'0 16px 40px rgba(0,0,0,.10), 0 32px 64px rgba(0,0,0,.10)','inner'=>'inset 0 2px 4px rgba(0,0,0,.04)'];
        $style_presets = [

            /* ── BUSINESS ──────────────────────────────────────────── */
            [
                'slug'=>'quiet-luxury','category'=>'business',
                'tone'=>__('Premium','ecf-framework'),'title'=>__('Quiet Luxury','ecf-framework'),
                'description'=>__('Dunkler Grafit-Text, sattes Pflaumenlila und ausgewogene Ecken — elegant und kontrastreich.','ecf-framework'),
                'heading_sample'=>__('Verfeinert, ohne laut zu wirken','ecf-framework'),
                'body_sample'=>__('Für Beratung, Kanzleien und Marken, die Tiefe und Klasse ausstrahlen wollen.','ecf-framework'),
                'heading_font_stack'=>'Georgia, "Times New Roman", serif',
                'body_font_stack'=>'system-ui, -apple-system, "Segoe UI", sans-serif',
                'preview'=>['background'=>'#faf7fb','primary'=>'#6d28d9','accent'=>'#0f766e'],
                'google_fonts'=>['heading'=>'Cormorant Garamond','body'=>'Jost'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'16px','base_body_font_weight'=>'500','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'70','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1200','format'=>'px'],'base_text_color'=>'#111827','base_background_color'=>'#faf7fb','link_color'=>'#6d28d9','focus_color'=>'#0f766e'],'colors'=>['primary'=>'#6d28d9','secondary'=>'#475569','accent'=>'#0f766e','surface'=>'#ffffff','text'=>'#111827'],'radius'=>['xs'=>['min'=>'5px','max'=>'5px'],'s'=>['min'=>'9px','max'=>'11px'],'m'=>['min'=>'13px','max'=>'16px'],'l'=>['min'=>'20px','max'=>'24px'],'xl'=>['min'=>'32px','max'=>'38px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows,'spacing'=>['min_base'=>'16','max_base'=>'26','min_ratio'=>'1.2','max_ratio'=>'1.333'],'fonts'=>['primary'=>'system-ui, -apple-system, "Segoe UI", sans-serif','secondary'=>'Georgia, "Times New Roman", serif']],
            ],
            [
                'slug'=>'corporate-clean','category'=>'business',
                'tone'=>__('Business','ecf-framework'),'title'=>__('Corporate Clean','ecf-framework'),
                'description'=>__('Kühles Navyblau, klare Struktur und zurückhaltende Runder für professionelle Unternehmensauftritte.','ecf-framework'),
                'heading_sample'=>__('Vertrauen durch Klarheit','ecf-framework'),
                'body_sample'=>__('Ideal für Unternehmen, Dienstleister und B2B-Marken, die seriös wirken müssen.','ecf-framework'),
                'heading_font_stack'=>'"Helvetica Neue", Arial, sans-serif',
                'body_font_stack'=>'system-ui, -apple-system, "Segoe UI", sans-serif',
                'preview'=>['background'=>'#f0f4f8','primary'=>'#1e3a5f','accent'=>'#0ea5e9'],
                'google_fonts'=>['heading'=>'Montserrat','body'=>'Inter'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'15px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'74','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1260','format'=>'px'],'base_text_color'=>'#1e293b','base_background_color'=>'#f0f4f8','link_color'=>'#1e3a5f','focus_color'=>'#0ea5e9'],'colors'=>['primary'=>'#1e3a5f','secondary'=>'#4a5568','accent'=>'#0ea5e9','surface'=>'#ffffff','text'=>'#1e293b'],'radius'=>['xs'=>['min'=>'3px','max'=>'4px'],'s'=>['min'=>'6px','max'=>'8px'],'m'=>['min'=>'9px','max'=>'12px'],'l'=>['min'=>'14px','max'=>'18px'],'xl'=>['min'=>'20px','max'=>'26px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows,'spacing'=>['min_base'=>'16','max_base'=>'26','min_ratio'=>'1.2','max_ratio'=>'1.333'],'fonts'=>['primary'=>'system-ui, -apple-system, "Segoe UI", sans-serif','secondary'=>'"Helvetica Neue", Arial, sans-serif']],
            ],
            [
                'slug'=>'kanzlei','category'=>'business',
                'tone'=>__('Seriös','ecf-framework'),'title'=>__('Kanzlei','ecf-framework'),
                'description'=>__('Warmes Weiß, Goldakzente und sehr sparsame Rundungen — wirkt gediegen, vertrauenswürdig und kompetent.','ecf-framework'),
                'heading_sample'=>__('Kompetenz, die man spürt','ecf-framework'),
                'body_sample'=>__('Für Kanzleien, Steuerberater, Notare und Finanzdienstleister.','ecf-framework'),
                'heading_font_stack'=>'"Palatino Linotype", "Book Antiqua", Palatino, Georgia, serif',
                'body_font_stack'=>'system-ui, -apple-system, "Segoe UI", sans-serif',
                'preview'=>['background'=>'#f9f6f0','primary'=>'#1a2e1a','accent'=>'#b8860b'],
                'google_fonts'=>['heading'=>'Playfair Display','body'=>'Lato'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'16px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'68','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1160','format'=>'px'],'base_text_color'=>'#1a1a1a','base_background_color'=>'#f9f6f0','link_color'=>'#1a2e1a','focus_color'=>'#b8860b'],'colors'=>['primary'=>'#1a2e1a','secondary'=>'#4b5563','accent'=>'#b8860b','surface'=>'#fffdf8','text'=>'#1a1a1a'],'radius'=>['xs'=>['min'=>'2px','max'=>'2px'],'s'=>['min'=>'4px','max'=>'5px'],'m'=>['min'=>'6px','max'=>'8px'],'l'=>['min'=>'10px','max'=>'14px'],'xl'=>['min'=>'16px','max'=>'20px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows_soft,'spacing'=>['min_base'=>'16','max_base'=>'28','min_ratio'=>'1.2','max_ratio'=>'1.333'],'fonts'=>['primary'=>'system-ui, -apple-system, "Segoe UI", sans-serif','secondary'=>'"Palatino Linotype", "Book Antiqua", Palatino, Georgia, serif']],
            ],

            /* ── KREATIV ───────────────────────────────────────────── */
            [
                'slug'=>'warm-editorial','category'=>'kreativ',
                'tone'=>__('Editorial','ecf-framework'),'title'=>__('Warm Editorial','ecf-framework'),
                'description'=>__('Cremige Oberflächen, elegante Serif-Überschriften und weiche Schatten für Geschichten und Premium-Content.','ecf-framework'),
                'heading_sample'=>__('Geschichten mit mehr Atmosphäre','ecf-framework'),
                'body_sample'=>__('Für Marken, Magazine und lange Leseerlebnisse, die Wärme und Tiefe ausstrahlen.','ecf-framework'),
                'heading_font_stack'=>'Iowan Old Style, Palatino, Georgia, serif',
                'body_font_stack'=>'Avenir Next, Avenir, Arial, sans-serif',
                'preview'=>['background'=>'#f5efe7','primary'=>'#7c3aed','accent'=>'#c2410c'],
                'google_fonts'=>['heading'=>'Playfair Display','body'=>'Lora'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'17px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'68','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1180','format'=>'px'],'base_text_color'=>'#1f2937','base_background_color'=>'#f5efe7','link_color'=>'#7c3aed','focus_color'=>'#c2410c'],'colors'=>['primary'=>'#7c3aed','secondary'=>'#6b7280','accent'=>'#c2410c','surface'=>'#fffaf3','text'=>'#1f2937'],'radius'=>['xs'=>['min'=>'4px','max'=>'4px'],'s'=>['min'=>'8px','max'=>'10px'],'m'=>['min'=>'12px','max'=>'14px'],'l'=>['min'=>'18px','max'=>'22px'],'xl'=>['min'=>'28px','max'=>'34px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows_soft,'spacing'=>['min_base'=>'17','max_base'=>'30','min_ratio'=>'1.2','max_ratio'=>'1.333'],'fonts'=>['primary'=>'Avenir Next, Avenir, "Helvetica Neue", Arial, sans-serif','secondary'=>'Iowan Old Style, "Palatino Linotype", Georgia, serif']],
            ],
            [
                'slug'=>'dark-studio','category'=>'kreativ',
                'tone'=>__('Studio','ecf-framework'),'title'=>__('Dark Studio','ecf-framework'),
                'description'=>__('Fast-schwarzer Hintergrund, kräftiges Violett und pinke Akzente — mutig, modern und ausdrucksstark.','ecf-framework'),
                'heading_sample'=>__('Design, das im Kopf bleibt','ecf-framework'),
                'body_sample'=>__('Für kreative Studios, Agenturen und Portfolios, die auffallen wollen.','ecf-framework'),
                'heading_font_stack'=>'"Helvetica Neue", Arial, sans-serif',
                'body_font_stack'=>'system-ui, -apple-system, sans-serif',
                'preview'=>['background'=>'#0f0f11','primary'=>'#a855f7','accent'=>'#ec4899'],
                'google_fonts'=>['heading'=>'Space Grotesk','body'=>'DM Sans'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'15px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'72','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1240','format'=>'px'],'base_text_color'=>'#e2e8f0','base_background_color'=>'#0f0f11','link_color'=>'#a855f7','focus_color'=>'#ec4899'],'colors'=>['primary'=>'#a855f7','secondary'=>'#6b7280','accent'=>'#ec4899','surface'=>'#1a1a1f','text'=>'#e2e8f0'],'radius'=>['xs'=>['min'=>'6px','max'=>'6px'],'s'=>['min'=>'10px','max'=>'12px'],'m'=>['min'=>'14px','max'=>'18px'],'l'=>['min'=>'22px','max'=>'28px'],'xl'=>['min'=>'36px','max'=>'44px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>['xs'=>'0 1px 3px rgba(0,0,0,.4)','s'=>'0 2px 8px rgba(0,0,0,.5)','m'=>'0 4px 16px rgba(0,0,0,.5)','l'=>'0 8px 24px rgba(0,0,0,.6)','xl'=>'0 16px 40px rgba(0,0,0,.6)','inner'=>'inset 0 2px 4px rgba(0,0,0,.3)'],'spacing'=>['min_base'=>'16','max_base'=>'28','min_ratio'=>'1.25','max_ratio'=>'1.414'],'fonts'=>['primary'=>'system-ui, -apple-system, sans-serif','secondary'=>'"Helvetica Neue", Arial, sans-serif']],
            ],
            [
                'slug'=>'bold-portfolio','category'=>'kreativ',
                'tone'=>__('Portfolio','ecf-framework'),'title'=>__('Bold Portfolio','ecf-framework'),
                'description'=>__('Reines Weiß, Fast-Schwarz und knalliges Rot — hoher Kontrast, wenig Ablenkung, maximale Wirkung.','ecf-framework'),
                'heading_sample'=>__('Arbeiten, die für sich sprechen','ecf-framework'),
                'body_sample'=>__('Für Designer, Fotografen und Kreative, die ihre Arbeit in den Vordergrund stellen.','ecf-framework'),
                'heading_font_stack'=>'"Helvetica Neue", Arial, sans-serif',
                'body_font_stack'=>'system-ui, -apple-system, sans-serif',
                'preview'=>['background'=>'#ffffff','primary'=>'#111111','accent'=>'#ef4444'],
                'google_fonts'=>['heading'=>'Barlow Condensed','body'=>'Barlow'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'15px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'76','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1280','format'=>'px'],'base_text_color'=>'#111111','base_background_color'=>'#ffffff','link_color'=>'#ef4444','focus_color'=>'#111111'],'colors'=>['primary'=>'#111111','secondary'=>'#6b7280','accent'=>'#ef4444','surface'=>'#f9f9f9','text'=>'#111111'],'radius'=>['xs'=>['min'=>'2px','max'=>'2px'],'s'=>['min'=>'4px','max'=>'4px'],'m'=>['min'=>'6px','max'=>'8px'],'l'=>['min'=>'10px','max'=>'14px'],'xl'=>['min'=>'16px','max'=>'20px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows,'spacing'=>['min_base'=>'18','max_base'=>'32','min_ratio'=>'1.333','max_ratio'=>'1.5'],'fonts'=>['primary'=>'system-ui, -apple-system, sans-serif','secondary'=>'"Helvetica Neue", Arial, sans-serif']],
            ],

            /* ── PRODUKT ───────────────────────────────────────────── */
            [
                'slug'=>'glass-product','category'=>'produkt',
                'tone'=>__('SaaS','ecf-framework'),'title'=>__('Glass Product','ecf-framework'),
                'description'=>__('Klare Produktoptik mit kühlen Indigoakzenten, luftigen Neutraltönen und weich gerundeten Flächen.','ecf-framework'),
                'heading_sample'=>__('Produktseiten, die Klarheit vermitteln','ecf-framework'),
                'body_sample'=>__('Ideal für SaaS, Produkt-Marketing und schnörkellose Interface-Marken.','ecf-framework'),
                'heading_font_stack'=>'Avenir Next, Avenir, "Helvetica Neue", Arial, sans-serif',
                'body_font_stack'=>'Inter, system-ui, sans-serif',
                'preview'=>['background'=>'#f8fafc','primary'=>'#4f46e5','accent'=>'#14b8a6'],
                'google_fonts'=>['heading'=>'Plus Jakarta Sans','body'=>'Inter'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'16px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'72','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1240','format'=>'px'],'base_text_color'=>'#0f172a','base_background_color'=>'#f8fafc','link_color'=>'#4f46e5','focus_color'=>'#0ea5e9'],'colors'=>['primary'=>'#4f46e5','secondary'=>'#64748b','accent'=>'#14b8a6','surface'=>'#ffffff','text'=>'#0f172a'],'radius'=>['xs'=>['min'=>'6px','max'=>'6px'],'s'=>['min'=>'10px','max'=>'12px'],'m'=>['min'=>'14px','max'=>'16px'],'l'=>['min'=>'20px','max'=>'24px'],'xl'=>['min'=>'30px','max'=>'36px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows,'spacing'=>['min_base'=>'16','max_base'=>'28','min_ratio'=>'1.25','max_ratio'=>'1.414'],'fonts'=>['primary'=>'Inter, system-ui, -apple-system, sans-serif','secondary'=>'Avenir Next, Avenir, "Helvetica Neue", Arial, sans-serif']],
            ],
            [
                'slug'=>'startup-spark','category'=>'produkt',
                'tone'=>__('Startup','ecf-framework'),'title'=>__('Startup Spark','ecf-framework'),
                'description'=>__('Warmes Orange trifft tiefes Violett — lebendig, energetisch und sofort einprägsam.','ecf-framework'),
                'heading_sample'=>__('Wachstum, das man spürt','ecf-framework'),
                'body_sample'=>__('Für Startups, Apps und neue Produkte, die Aufmerksamkeit und Vertrauen gleichzeitig wollen.','ecf-framework'),
                'heading_font_stack'=>'"Helvetica Neue", Arial, sans-serif',
                'body_font_stack'=>'system-ui, -apple-system, sans-serif',
                'preview'=>['background'=>'#fffbf0','primary'=>'#f97316','accent'=>'#7c3aed'],
                'google_fonts'=>['heading'=>'Poppins','body'=>'Nunito'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'16px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'72','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1240','format'=>'px'],'base_text_color'=>'#1c1917','base_background_color'=>'#fffbf0','link_color'=>'#f97316','focus_color'=>'#7c3aed'],'colors'=>['primary'=>'#f97316','secondary'=>'#78716c','accent'=>'#7c3aed','surface'=>'#ffffff','text'=>'#1c1917'],'radius'=>['xs'=>['min'=>'8px','max'=>'8px'],'s'=>['min'=>'12px','max'=>'14px'],'m'=>['min'=>'16px','max'=>'20px'],'l'=>['min'=>'24px','max'=>'30px'],'xl'=>['min'=>'36px','max'=>'44px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows,'spacing'=>['min_base'=>'16','max_base'=>'28','min_ratio'=>'1.25','max_ratio'=>'1.414'],'fonts'=>['primary'=>'system-ui, -apple-system, sans-serif','secondary'=>'"Helvetica Neue", Arial, sans-serif']],
            ],
            [
                'slug'=>'storefront','category'=>'produkt',
                'tone'=>__('Shop','ecf-framework'),'title'=>__('Storefront','ecf-framework'),
                'description'=>__('Warmes Weiß, Teal und Amber — freundlich, konversionsorientiert und leicht zu scannen.','ecf-framework'),
                'heading_sample'=>__('Kaufen, so einfach wie möglich','ecf-framework'),
                'body_sample'=>__('Für Online-Shops, Marktplätze und Produktseiten, bei denen jeder Klick zählt.','ecf-framework'),
                'heading_font_stack'=>'"Helvetica Neue", Arial, sans-serif',
                'body_font_stack'=>'system-ui, -apple-system, sans-serif',
                'preview'=>['background'=>'#fdfaf7','primary'=>'#0d9488','accent'=>'#f59e0b'],
                'google_fonts'=>['heading'=>'Nunito','body'=>'Open Sans'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'15px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'74','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1280','format'=>'px'],'base_text_color'=>'#1c1917','base_background_color'=>'#fdfaf7','link_color'=>'#0d9488','focus_color'=>'#f59e0b'],'colors'=>['primary'=>'#0d9488','secondary'=>'#6b7280','accent'=>'#f59e0b','surface'=>'#ffffff','text'=>'#1c1917'],'radius'=>['xs'=>['min'=>'5px','max'=>'6px'],'s'=>['min'=>'8px','max'=>'10px'],'m'=>['min'=>'12px','max'=>'14px'],'l'=>['min'=>'18px','max'=>'22px'],'xl'=>['min'=>'28px','max'=>'34px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows,'spacing'=>['min_base'=>'16','max_base'=>'26','min_ratio'=>'1.2','max_ratio'=>'1.333'],'fonts'=>['primary'=>'system-ui, -apple-system, sans-serif','secondary'=>'"Helvetica Neue", Arial, sans-serif']],
            ],

            /* ── PERSÖNLICH ────────────────────────────────────────── */
            [
                'slug'=>'notebook','category'=>'persoenlich',
                'tone'=>__('Blog','ecf-framework'),'title'=>__('Notebook','ecf-framework'),
                'description'=>__('Cremefarbener Hintergrund, warme Brauntöne und lesefreundliche Serifenschrift — wie ein gutes Buch.','ecf-framework'),
                'heading_sample'=>__('Gedanken, die hängen bleiben','ecf-framework'),
                'body_sample'=>__('Für Blogs, Newsletter-Seiten und Content-Creators, die Lesbarkeit über alles stellen.','ecf-framework'),
                'heading_font_stack'=>'Georgia, "Times New Roman", serif',
                'body_font_stack'=>'system-ui, -apple-system, sans-serif',
                'preview'=>['background'=>'#fef9ef','primary'=>'#92400e','accent'=>'#4d7c0f'],
                'google_fonts'=>['heading'=>'Lora','body'=>'Source Serif 4'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'17px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'65','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1120','format'=>'px'],'base_text_color'=>'#292524','base_background_color'=>'#fef9ef','link_color'=>'#92400e','focus_color'=>'#4d7c0f'],'colors'=>['primary'=>'#92400e','secondary'=>'#78716c','accent'=>'#4d7c0f','surface'=>'#fffef5','text'=>'#292524'],'radius'=>['xs'=>['min'=>'4px','max'=>'4px'],'s'=>['min'=>'7px','max'=>'9px'],'m'=>['min'=>'10px','max'=>'13px'],'l'=>['min'=>'16px','max'=>'20px'],'xl'=>['min'=>'24px','max'=>'30px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows_soft,'spacing'=>['min_base'=>'18','max_base'=>'30','min_ratio'=>'1.2','max_ratio'=>'1.333'],'fonts'=>['primary'=>'system-ui, -apple-system, sans-serif','secondary'=>'Georgia, "Times New Roman", serif']],
            ],
            [
                'slug'=>'pure-minimal','category'=>'persoenlich',
                'tone'=>__('Minimal','ecf-framework'),'title'=>__('Pure Minimal','ecf-framework'),
                'description'=>__('Reines Weiß, dezente Grautöne und kaum sichtbare Schatten — Inhalt steht, nichts stört.','ecf-framework'),
                'heading_sample'=>__('Weniger, das mehr sagt','ecf-framework'),
                'body_sample'=>__('Für Freelancer, Autoren und alle, bei denen der Inhalt ohne Ablenkung für sich sprechen soll.','ecf-framework'),
                'heading_font_stack'=>'"Helvetica Neue", Arial, sans-serif',
                'body_font_stack'=>'system-ui, -apple-system, sans-serif',
                'preview'=>['background'=>'#ffffff','primary'=>'#1a1a2e','accent'=>'#60a5fa'],
                'google_fonts'=>['heading'=>'DM Sans','body'=>'DM Sans'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'16px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'70','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1200','format'=>'px'],'base_text_color'=>'#1a1a2e','base_background_color'=>'#ffffff','link_color'=>'#60a5fa','focus_color'=>'#1a1a2e'],'colors'=>['primary'=>'#1a1a2e','secondary'=>'#9ca3af','accent'=>'#60a5fa','surface'=>'#f9fafb','text'=>'#1a1a2e'],'radius'=>['xs'=>['min'=>'3px','max'=>'3px'],'s'=>['min'=>'5px','max'=>'6px'],'m'=>['min'=>'8px','max'=>'10px'],'l'=>['min'=>'12px','max'=>'16px'],'xl'=>['min'=>'18px','max'=>'24px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows_soft,'spacing'=>['min_base'=>'18','max_base'=>'32','min_ratio'=>'1.25','max_ratio'=>'1.414'],'fonts'=>['primary'=>'system-ui, -apple-system, sans-serif','secondary'=>'"Helvetica Neue", Arial, sans-serif']],
            ],
            [
                'slug'=>'personal-brand','category'=>'persoenlich',
                'tone'=>__('Personal','ecf-framework'),'title'=>__('Personal Brand','ecf-framework'),
                'description'=>__('Frisches Minzgrün und tiefes Blau — persönlich, warm und unverwechselbar ohne aufdringlich zu sein.','ecf-framework'),
                'heading_sample'=>__('Authentisch und einprägsam','ecf-framework'),
                'body_sample'=>__('Für Coaches, Speaker, Berater und alle, die sich als Person zur Marke machen wollen.','ecf-framework'),
                'heading_font_stack'=>'"Helvetica Neue", Arial, sans-serif',
                'body_font_stack'=>'system-ui, -apple-system, sans-serif',
                'preview'=>['background'=>'#f0fdf4','primary'=>'#059669','accent'=>'#1d4ed8'],
                'google_fonts'=>['heading'=>'Raleway','body'=>'Nunito'],
                'preset'=>['general'=>['root_font_size'=>'62.5','base_body_text_size'=>'16px','base_body_font_weight'=>'400','base_font_family'=>'var(--ecf-font-primary)','heading_font_family'=>'var(--ecf-font-secondary)','content_max_width'=>['value'=>'70','format'=>'ch'],'elementor_boxed_width'=>['value'=>'1200','format'=>'px'],'base_text_color'=>'#064e3b','base_background_color'=>'#f0fdf4','link_color'=>'#059669','focus_color'=>'#1d4ed8'],'colors'=>['primary'=>'#059669','secondary'=>'#6b7280','accent'=>'#1d4ed8','surface'=>'#ffffff','text'=>'#064e3b'],'radius'=>['xs'=>['min'=>'6px','max'=>'8px'],'s'=>['min'=>'10px','max'=>'12px'],'m'=>['min'=>'14px','max'=>'18px'],'l'=>['min'=>'22px','max'=>'28px'],'xl'=>['min'=>'34px','max'=>'42px'],'full'=>['min'=>'999px','max'=>'999px']],'shadows'=>$sp_shadows_soft,'spacing'=>['min_base'=>'16','max_base'=>'28','min_ratio'=>'1.25','max_ratio'=>'1.414'],'fonts'=>['primary'=>'system-ui, -apple-system, sans-serif','secondary'=>'"Helvetica Neue", Arial, sans-serif']],
            ],
        ];

        /* ── Custom presets ─────────────────────────────────────────── */
        $custom_presets = get_option( 'ecf_custom_presets', [] );

        /* ── Design health ───────────────────────────────────────────── */
        $design_health = $this->website_design_health_checks( $settings );

        /* ── Smart recommendations ───────────────────────────────────── */
        $smart_recs = $this->website_smart_recommendations( $settings );

        /* ── Limit snapshot ──────────────────────────────────────────── */
        $limit_snap = $this->get_elementor_limit_snapshot();
        ?>
<?php
  $v2_btn_fs  = (int) ( $settings['ui_btn_font_size']  ?? 12 );
  $v2_base_fs = (int) ( $settings['ui_base_font_size'] ?? 13 );
  $v2_nav_fs  = (int) ( $settings['ui_nav_font_size']  ?? 13 );
  $v2_font    = sanitize_text_field( $settings['ui_font_family'] ?? '' );
  $v2_font_val = $v2_font ?: "'Plus Jakarta Sans', system-ui, sans-serif";
?>
<div class="ecf-v2-wrapper" id="ecf-v2-wrapper" style="--v2-btn-fs:<?php echo esc_attr( $v2_btn_fs ); ?>px;--v2-ui-base-fs:<?php echo esc_attr( $v2_base_fs ); ?>px;--v2-ui-nav-fs:<?php echo esc_attr( $v2_nav_fs ); ?>px;--v2-font:<?php echo esc_attr( $v2_font_val ); ?>">

<!-- ═══ v2 Sidebar ═══════════════════════════════════════════════════ -->
<nav class="v2-sb">
  <div class="v2-sb-head">
    <div class="v2-sb-logo"><div class="v2-sb-dot"></div>Layrix</div>
    <div class="v2-sb-byline"><span>Alexander Kaiser</span><span class="v2-ver">v<?php echo esc_html( $ver ); ?></span></div>
  </div>
  <div class="v2-sb-nav">
    <div class="v2-sb-group"><?php esc_html_e( 'Tokens', 'ecf-framework' ); ?></div>
    <button type="button" class="v2-ni" data-v2-page="colors">
      <svg viewBox="0 0 13 13" fill="none"><circle cx="4" cy="4" r="2.2" fill="currentColor" opacity=".7"/><circle cx="9" cy="4" r="2.2" fill="currentColor" opacity=".5"/><circle cx="4" cy="9" r="2.2" fill="currentColor" opacity=".5"/><circle cx="9" cy="9" r="2.2" fill="currentColor" opacity=".3"/></svg>
      <?php esc_html_e( 'Farben', 'ecf-framework' ); ?>
    </button>
    <button type="button" class="v2-ni" data-v2-page="radius">
      <svg viewBox="0 0 13 13" fill="none"><rect x="1.5" y="1.5" width="10" height="10" rx="3.5" stroke="currentColor" stroke-width="1.2" opacity=".7"/></svg>
      <?php esc_html_e( 'Radius', 'ecf-framework' ); ?>
    </button>
    <button type="button" class="v2-ni" data-v2-page="typography">
      <svg viewBox="0 0 13 13" fill="currentColor"><path d="M1 3h11v1H1V3zm0 3h8v1H1V6zm0 3h9v1H1V9z" opacity=".6"/></svg>
      <?php esc_html_e( 'Typografie', 'ecf-framework' ); ?>
    </button>
    <button type="button" class="v2-ni" data-v2-page="spacing">
      <svg viewBox="0 0 13 13" fill="currentColor"><rect x="1" y="1" width="4" height="4" rx=".8" opacity=".4"/><rect x="7" y="1" width="5" height="5" rx=".8" opacity=".7"/><rect x="1" y="7" width="5" height="5" rx=".8" opacity=".7"/></svg>
      <?php esc_html_e( 'Abstände', 'ecf-framework' ); ?>
    </button>
    <button type="button" class="v2-ni" data-v2-page="shadows">
      <svg viewBox="0 0 13 13" fill="none"><rect x="1.5" y="1.5" width="10" height="10" rx="2.5" stroke="currentColor" stroke-width="1.1" opacity=".5"/></svg>
      <?php esc_html_e( 'Schatten', 'ecf-framework' ); ?>
    </button>
    <button type="button" class="v2-ni" data-v2-page="variables">
      <svg viewBox="0 0 13 13" fill="currentColor"><path d="M6.5 1l1.1 3.4H11L8.3 6.5l1 3-2.8-2-2.8 2 1-3L2 4.4h3.4L6.5 1z" opacity=".5"/></svg>
      <?php esc_html_e( 'Variablen', 'ecf-framework' ); ?>
    </button>
    <button type="button" class="v2-ni" data-v2-page="preview">
      <svg viewBox="0 0 13 13" fill="none"><rect x="1.5" y="2.5" width="10" height="8" rx="1.5" stroke="currentColor" stroke-width="1.1" opacity=".6"/><path d="M1.5 5h10" stroke="currentColor" stroke-width="1" opacity=".3"/></svg>
      <?php esc_html_e( 'Vorschau', 'ecf-framework' ); ?>
    </button>
    <div class="v2-sb-group" style="margin-top:2px"><?php esc_html_e( 'Klassen', 'ecf-framework' ); ?></div>
    <button type="button" class="v2-ni" data-v2-page="classes">
      <svg viewBox="0 0 13 13" fill="currentColor"><rect x="1" y="1" width="4.5" height="4.5" rx=".7" opacity=".5"/><rect x="7.5" y="1" width="4.5" height="4.5" rx=".7" opacity=".5"/><rect x="1" y="7.5" width="4.5" height="4.5" rx=".7" opacity=".5"/><rect x="7.5" y="7.5" width="4.5" height="4.5" rx=".7" opacity=".5"/></svg>
      <?php esc_html_e( 'Klassen-Auswahl', 'ecf-framework' ); ?>
    </button>
    <button type="button" class="v2-ni" data-v2-page="cookbook">
      <svg viewBox="0 0 13 13" fill="none"><path d="M2 2.5C2 2.22 2.22 2 2.5 2h6c.83 0 1.5.67 1.5 1.5v7c0 .28-.22.5-.5.5h-6A1.5 1.5 0 0 1 2 9.5v-7z" stroke="currentColor" stroke-width="1.1" opacity=".7"/><path d="M4.5 4.5h3M4.5 6.5h3M4.5 8.5h2" stroke="currentColor" stroke-width="1" stroke-linecap="round" opacity=".55"/></svg>
      <?php esc_html_e( 'Anwendung', 'ecf-framework' ); ?>
    </button>
    <div class="v2-sb-group" style="margin-top:2px"><?php esc_html_e( 'Workflow', 'ecf-framework' ); ?></div>
    <button type="button" class="v2-ni" data-v2-page="sync">
      <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 2v7M4 7l2.5 2.5L9 7" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M1.5 10.5h10" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" opacity=".35"/></svg>
      <?php esc_html_e( 'Sync & Export', 'ecf-framework' ); ?>
      <span class="v2-ni-dot" id="v2-sync-dot" style="display:none" title="<?php esc_attr_e( 'Klassen wurden verändert — Sync empfohlen', 'ecf-framework' ); ?>"></span>
    </button>
  </div>
  <div class="v2-sb-foot">
    <button type="button" class="v2-ni v2-ni--search" id="v2-search-trigger" title="<?php esc_attr_e( 'Suchen (Strg+K)', 'ecf-framework' ); ?>">
      <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="3.5" stroke="currentColor" stroke-width="1.2" opacity=".6"/><path d="M8.5 8.5L11 11" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" opacity=".6"/></svg>
      <?php esc_html_e( 'Suchen', 'ecf-framework' ); ?>
      <span class="v2-ni-kbd">⌘K</span>
    </button>
    <button type="button" class="v2-ni" id="v2-history-trigger" title="<?php esc_attr_e( 'Gespeicherte Versionen', 'ecf-framework' ); ?>">
      <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 1v3.5l2.5 1.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" opacity=".6"/><path d="M6.5 1a5.5 5.5 0 100 11A5.5 5.5 0 006.5 1z" stroke="currentColor" stroke-width="1.1" opacity=".4"/></svg>
      <?php esc_html_e( 'Verlauf', 'ecf-framework' ); ?>
    </button>
    <button type="button" class="v2-ni" data-v2-page="help">
      <svg viewBox="0 0 13 13" fill="currentColor"><path d="M6.5 1a5.5 5.5 0 100 11A5.5 5.5 0 006.5 1zm0 2.5a.75.75 0 110 1.5.75.75 0 010-1.5zm.75 5.5h-1.5V6h1.5v3z" opacity=".5"/></svg>
      <?php esc_html_e( 'Erste Schritte', 'ecf-framework' ); ?>
    </button>
    <button type="button" class="v2-ni" data-v2-page="settings">
      <svg viewBox="0 0 13 13" fill="currentColor"><path d="M6.5 1a5.5 5.5 0 100 11A5.5 5.5 0 006.5 1zM5 6.5a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z" opacity=".5"/></svg>
      <?php esc_html_e( 'Einstellungen', 'ecf-framework' ); ?>
    </button>
    <?php $this->render_owner_notes_sidebar_button(); ?>
  </div>
</nav>

<!-- ═══ SUCHE (Cmd+K) ════════════════════════════════���═══════════════ -->
<div id="v2-search-modal" class="v2-search-overlay" hidden>
  <div class="v2-search-box">
    <div class="v2-search-input-row">
      <svg width="14" height="14" viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="3.5" stroke="currentColor" stroke-width="1.3" opacity=".5"/><path d="M8.5 8.5L11 11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" opacity=".5"/></svg>
      <input type="text" id="v2-search-input" class="v2-search-inp" placeholder="<?php esc_attr_e( 'Token, Klasse oder Seite suchen…', 'ecf-framework' ); ?>" autocomplete="off">
      <kbd class="v2-search-esc">Esc</kbd>
    </div>
    <div id="v2-search-results" class="v2-search-results"></div>
  </div>
</div>

<!-- ═══ VERLAUF ══════════════════════════════════════════════════════ -->
<div id="v2-history-modal" class="v2-modal-overlay v2-history-overlay" hidden>
  <div class="v2-modal-box v2-history-box">
    <div class="v2-modal-head">
      <span><?php esc_html_e( 'Versionsverlauf', 'ecf-framework' ); ?></span>
      <div style="display:flex;align-items:center;gap:8px;margin-left:auto">
        <button type="button" class="v2-btn v2-btn--ghost v2-btn--xs" id="v2-history-diff-toggle"><?php esc_html_e( 'Vergleichen', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-modal-close" id="v2-history-close">✕</button>
      </div>
    </div>
    <div id="v2-history-diff-hint" class="v2-history-diff-hint" style="display:none">
      <?php esc_html_e( 'Zwei Versionen auswählen um sie zu vergleichen.', 'ecf-framework' ); ?>
    </div>
    <div id="v2-history-list" class="v2-history-list">
      <p style="color:var(--v2-text3);font-size:12px;padding:16px"><?php esc_html_e( 'Noch keine Verlauf-Einträge vorhanden. Änderungen werden beim Speichern automatisch gesichert.', 'ecf-framework' ); ?></p>
    </div>
    <div id="v2-history-diff-result" class="v2-history-diff-result" style="display:none"></div>
  </div>
</div>

<!-- ═══ KIT IMPORT MODAL (Theme-Style-Importer) ═════════════════════ -->
<div id="ecf-kit-import-modal" class="v2-modal-overlay" style="display:none;align-items:center;justify-content:center">
  <div class="v2-modal-box" style="max-width:680px;width:100%;max-height:80vh;display:flex;flex-direction:column">
    <div class="v2-modal-head">
      <span><?php esc_html_e( 'Aus Elementor-Kit importieren', 'ecf-framework' ); ?></span>
      <button type="button" class="v2-modal-close" data-ecf-kit-close>✕</button>
    </div>
    <div id="ecf-kit-import-body" style="padding:18px;overflow-y:auto;flex:1">
      <p id="ecf-kit-import-loading" style="color:var(--v2-text3);font-size:13px"><?php esc_html_e( 'Lade Elementor-Kit-Daten…', 'ecf-framework' ); ?></p>
      <div id="ecf-kit-import-empty" style="display:none;color:var(--v2-text3);font-size:13px;padding:30px;text-align:center">
        <div style="font-size:32px;margin-bottom:8px">📭</div>
        <?php esc_html_e( 'Kein aktives Elementor-Kit gefunden oder keine Werte hinterlegt.', 'ecf-framework' ); ?>
      </div>
      <div id="ecf-kit-import-fields" style="display:none">
        <p style="color:var(--v2-text2);font-size:12.5px;line-height:1.5;margin:0 0 14px">
          <?php esc_html_e( 'Wähle die Felder aus, die du aus deinem aktiven Elementor-Kit übernehmen möchtest. Bestehende Layrix-Werte werden überschrieben.', 'ecf-framework' ); ?>
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:12.5px">
          <thead>
            <tr style="border-bottom:1px solid var(--v2-border);color:var(--v2-text3);font-size:10.5px;text-transform:uppercase;letter-spacing:.04em">
              <th style="text-align:left;padding:8px 4px;width:22px"><input type="checkbox" id="ecf-kit-toggle-all" checked title="<?php esc_attr_e( 'Alle umschalten', 'ecf-framework' ); ?>"></th>
              <th style="text-align:left;padding:8px 4px"><?php esc_html_e( 'Feld', 'ecf-framework' ); ?></th>
              <th style="text-align:left;padding:8px 4px"><?php esc_html_e( 'Wert aus Elementor', 'ecf-framework' ); ?></th>
            </tr>
          </thead>
          <tbody id="ecf-kit-import-tbody"></tbody>
        </table>
      </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;padding:14px 18px;border-top:1px solid var(--v2-border)">
      <button type="button" class="v2-btn v2-btn--ghost" data-ecf-kit-close><?php esc_html_e( 'Abbrechen', 'ecf-framework' ); ?></button>
      <button type="button" class="v2-btn v2-btn--primary" id="ecf-kit-import-apply" disabled><?php esc_html_e( 'Auswahl übernehmen', 'ecf-framework' ); ?></button>
    </div>
  </div>
</div>

<!-- ═══ v2 Shell ═════════════════════════════════════════════════════ -->
<div class="v2-shell">
<form id="ecf-v2-form" method="post" autocomplete="off">

<?php if ( ( $limit_snap['classes_limit'] ?? 100 ) > 100 ) : ?>
<div class="v2-class-limit-banner" id="v2-class-limit-banner">
  <span class="v2-clb-icon">✦</span>
  <?php printf(
    esc_html__( 'Dein Elementor erlaubt bis zu %d Globale Klassen — mehr als der Standard-Wert von 100.', 'ecf-framework' ),
    (int) ( $limit_snap['classes_limit'] ?? 100 )
  ); ?>
</div>
<?php endif; ?>

<div id="v2-autosave-pill" class="v2-autosave-pill v2-autosave-pill--hidden"></div>
<span id="v2-last-saved" class="v2-last-saved v2-last-saved--hidden"></span>

<!-- ═══ PAGE: FARBEN & RADIUS ═══════════════════════════════════════ -->
<div id="ecf-v2-page-colors" class="v2-page v2-page--on">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Farben', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <div class="v2-actions-menu">
        <button type="button" class="v2-btn v2-btn--primary v2-actions-toggle" data-v2-actions-toggle aria-haspopup="menu" aria-expanded="false" title="<?php esc_attr_e( 'Weitere Aktionen', 'ecf-framework' ); ?>"><span class="v2-actions-toggle__label"><?php esc_html_e( 'Aktionen', 'ecf-framework' ); ?></span><span class="v2-actions-toggle__chevron" aria-hidden="true">▾</span></button>
        <div class="v2-actions-menu__dropdown" role="menu" hidden>
          <button type="button" role="menuitem" class="v2-actions-menu__item" data-ecf-kit-import title="<?php esc_attr_e( 'Farben/Schriften aus dem aktiven Elementor-Kit übernehmen', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">↘</span><span><?php esc_html_e( 'Aus Elementor importieren', 'ecf-framework' ); ?></span></button>
          <button type="button" role="menuitem" class="v2-actions-menu__item v2-actions-menu__item--danger" data-v2-reset-defaults title="<?php esc_attr_e( 'Reset ALL settings to plugin defaults', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">↺</span><span><?php esc_html_e( 'Auf Grundeinstellungen zurücksetzen', 'ecf-framework' ); ?></span></button>
          <button type="button" role="menuitem" class="v2-actions-menu__item" data-v2-reset title="<?php esc_attr_e( 'Discard unsaved changes and reload page', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">✕</span><span><?php esc_html_e( 'Änderungen verwerfen', 'ecf-framework' ); ?></span></button>
        </div>
      </div>
      <button type="button" class="v2-btn v2-btn--outline" data-v2-save><?php esc_html_e( 'Speichern', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Farben', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Deine Website-Farben — auf eine Zeile klicken zum Bearbeiten.', 'ecf-framework' ); ?></p></div>

      <!-- Colors -->
      <!-- ── Harmonie-Generator ──────────────────────────────────── -->
      <div class="v2-hg" id="v2-harmony-generator">
        <div class="v2-hg-header">
          <span class="v2-hg-icon">✦</span>
          <span class="v2-hg-title"><?php esc_html_e( 'Harmonie-Generator', 'ecf-framework' ); ?></span>
          <div class="v2-hg-mode-btns">
            <button type="button" class="v2-hg-mode is-active" data-hg-mode="complementary"><?php esc_html_e( 'Komplementär', 'ecf-framework' ); ?></button>
            <button type="button" class="v2-hg-mode" data-hg-mode="analogous"><?php esc_html_e( 'Analog', 'ecf-framework' ); ?></button>
            <button type="button" class="v2-hg-mode" data-hg-mode="triadic"><?php esc_html_e( 'Triadisch', 'ecf-framework' ); ?></button>
            <button type="button" class="v2-hg-mode" data-hg-mode="split"><?php esc_html_e( 'Split', 'ecf-framework' ); ?></button>
            <button type="button" class="v2-hg-mode" data-hg-mode="tetradic"><?php esc_html_e( 'Tetradic', 'ecf-framework' ); ?></button>
            <button type="button" class="v2-hg-mode" data-hg-mode="monochromatic"><?php esc_html_e( 'Monochromatic', 'ecf-framework' ); ?></button>
          </div>
          <div class="v2-hg-sliders">
            <label class="v2-hg-slider-row">
              <span class="v2-hg-slider-lbl"><?php esc_html_e( 'Brightness', 'ecf-framework' ); ?></span>
              <input type="range" id="v2-hg-sl-light" class="v2-hg-slider" min="-50" max="50" value="0">
              <span class="v2-hg-slider-val" id="v2-hg-sl-light-val">0</span>
            </label>
            <label class="v2-hg-slider-row">
              <span class="v2-hg-slider-lbl"><?php esc_html_e( 'Saturation', 'ecf-framework' ); ?></span>
              <input type="range" id="v2-hg-sl-sat" class="v2-hg-slider" min="-50" max="50" value="0">
              <span class="v2-hg-slider-val" id="v2-hg-sl-sat-val">0</span>
            </label>
          </div>
          <div class="v2-hg-header-actions">
            <label class="v2-btn v2-btn--ghost v2-btn--sm v2-hg-img-btn" title="<?php esc_attr_e( 'Extract colors from image', 'ecf-framework' ); ?>">
              <svg viewBox="0 0 20 20" fill="none" width="13" height="13"><rect x="2" y="4" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><circle cx="7" cy="8.5" r="1.5" fill="currentColor"/><path d="M2 13l4-4 3 3 3-3 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <?php esc_html_e( 'From Image', 'ecf-framework' ); ?>
              <input type="file" id="v2-hg-img-input" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%">
            </label>
            <button type="button" class="v2-btn v2-btn--ghost v2-btn--sm v2-hg-shuffle" id="v2-hg-shuffle" title="<?php esc_attr_e( 'Zufällige Harmonie generieren', 'ecf-framework' ); ?>">
              <svg viewBox="0 0 20 20" fill="none" width="13" height="13"><path d="M3 6h10M3 14h10M13 3l4 3-4 3M13 11l4 3-4 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <?php esc_html_e( 'Zufall', 'ecf-framework' ); ?>
            </button>
          </div>
        </div>

        <!-- Prominenter Apply-CTA — oben, zentriert -->
        <div class="v2-hg-apply-bar">
          <div class="v2-hg-apply-hint"><?php esc_html_e( 'Zufrieden mit der Palette?', 'ecf-framework' ); ?></div>
          <button type="button" class="v2-btn v2-btn--primary v2-hg-apply-cta" id="v2-hg-apply">
            <svg viewBox="0 0 20 20" fill="none" width="14" height="14" style="margin-right:6px"><path d="M5 10l3.5 3.5L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?php esc_html_e( 'In Palette übernehmen', 'ecf-framework' ); ?>
          </button>
        </div>

        <div class="v2-hg-swatches" id="v2-hg-swatches">
          <?php
          $hg_labels = [
            __('Primary','ecf-framework'),
            __('Secondary','ecf-framework'),
            __('Accent','ecf-framework'),
            __('Surface','ecf-framework'),
            __('Text','ecf-framework'),
          ];
          $hg_palette_keys = ['primary','secondary','accent','surface','text'];
          $hg_fallbacks    = ['#6366f1','#f163be','#63c3f1','#f4f4f8','#0f0f18'];
          $hg_defaults = array_map(
            function($key, $fallback) use ($c) { return $c[$key] ?? $fallback; },
            $hg_palette_keys, $hg_fallbacks
          );
          foreach ($hg_labels as $hi => $hlabel):
          ?>
          <div class="v2-hg-slot" data-hg-slot="<?php echo $hi; ?>" data-hg-locked="0">
            <button type="button" class="v2-hg-lock" data-hg-lock="<?php echo $hi; ?>" title="<?php esc_attr_e('Farbe sperren','ecf-framework'); ?>">
              <!-- offen: Bügel rechts offen -->
              <svg class="v2-hg-lock-open" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="11" width="18" height="12" rx="2.5" fill="currentColor" fill-opacity=".15" stroke="currentColor" stroke-width="2"/>
                <path d="M7 11V7.5a5 5 0 0 1 9.5-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="17" r="1.5" fill="currentColor"/>
              </svg>
              <!-- geschlossen: Bügel zu, filled -->
              <svg class="v2-hg-lock-closed" viewBox="0 0 24 24" fill="currentColor" style="display:none">
                <rect x="3" y="11" width="18" height="12" rx="2.5"/>
                <path d="M7 11V7.5a5 5 0 0 1 10 0V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
                <circle cx="12" cy="17" r="1.5" fill="white" fill-opacity=".8"/>
              </svg>
            </button>
            <div class="v2-hg-sw-block" style="background:<?php echo esc_attr($hg_defaults[$hi]); ?>">
              <span class="v2-hg-sw-hex"><?php echo esc_html($hg_defaults[$hi]); ?></span>
              <div class="v2-hg-popover" hidden>
                <div class="v2-hg-fmt-tabs">
                  <button type="button" class="v2-hg-fmt is-active" data-fmt="hex">HEX</button>
                  <button type="button" class="v2-hg-fmt" data-fmt="rgb">RGB</button>
                  <button type="button" class="v2-hg-fmt" data-fmt="hsl">HSL</button>
                  <label class="v2-hg-picker-btn" title="<?php esc_attr_e('Farbauswahl öffnen','ecf-framework'); ?>">
                    <input type="color" class="v2-hg-slot-picker" value="<?php echo esc_attr($hg_defaults[$hi]); ?>">
                    <svg viewBox="0 0 16 16" fill="none" width="12" height="12"><circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.5"/><circle cx="8" cy="8" r="2" fill="currentColor"/></svg>
                  </label>
                </div>
                <div class="v2-hg-fmt-panel" data-panel="hex">
                  <input type="text" class="v2-si v2-hg-slot-hex" value="<?php echo esc_attr($hg_defaults[$hi]); ?>" maxlength="7" placeholder="#000000" spellcheck="false">
                </div>
                <div class="v2-hg-fmt-panel" data-panel="rgb" hidden>
                  <input type="number" class="v2-si v2-hg-rgb" data-ch="r" min="0" max="255" placeholder="R">
                  <input type="number" class="v2-si v2-hg-rgb" data-ch="g" min="0" max="255" placeholder="G">
                  <input type="number" class="v2-si v2-hg-rgb" data-ch="b" min="0" max="255" placeholder="B">
                </div>
                <div class="v2-hg-fmt-panel" data-panel="hsl" hidden>
                  <input type="number" class="v2-si v2-hg-hsl" data-ch="h" min="0" max="360" placeholder="H">
                  <input type="number" class="v2-si v2-hg-hsl" data-ch="s" min="0" max="100" placeholder="S">
                  <input type="number" class="v2-si v2-hg-hsl" data-ch="l" min="0" max="100" placeholder="L">
                </div>
              </div>
            </div>
            <div class="v2-hg-sw-foot">
              <span class="v2-hg-sw-lbl"><?php echo esc_html($hlabel); ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>

      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Farbpalette', 'ecf-framework' ); ?></div>
        <div class="v2-tl" data-v2-tl-color>
          <?php
          $color_de_labels = [
            'primary'   => __( 'Primary',   'ecf-framework' ),
            'secondary' => __( 'Secondary', 'ecf-framework' ),
            'accent'    => __( 'Accent',    'ecf-framework' ),
            'surface'   => __( 'Surface',   'ecf-framework' ),
            'text'      => __( 'Text',      'ecf-framework' ),
          ];
          foreach ( $colors_arr as $i => $row ) :
            $cname      = sanitize_key( $row['name'] ?? '' );
            $cval       = $row['value'] ?? '#000000';
            $chex       = $c[ $cname ] ?? '#000000';
            $cshade_dat = $shade_data[ $cname ] ?? [ 'shades' => [], 'tints' => [] ];
            $cfmt       = $row['format'] ?? 'hex';
            $cde_label  = $color_de_labels[ $cname ] ?? $cname;
            if ( ! $cname ) continue;
          ?>
          <div class="v2-tr" id="v2-tr-<?php echo esc_attr( $cname ); ?>">
            <div class="v2-tr-main">
              <div class="v2-tr-sw" id="v2-sw-<?php echo esc_attr( $cname ); ?>" style="background:<?php echo esc_attr( $chex ); ?>"></div>
              <div>
                <div class="v2-tr-name"><?php echo esc_html( $cde_label ); ?></div>
                <div class="v2-tr-var">--ecf-color-<?php echo esc_html( $cname ); ?></div>
              </div>
              <div class="v2-tr-meta">
                <span class="v2-chip v2-chip--hi v2-chip--clickable" id="v2-hex-<?php echo esc_attr( $cname ); ?>" onclick="event.stopPropagation();ecfV2ToggleEdit('<?php echo esc_js( $cname ); ?>')" title="<?php esc_attr_e( 'Farbe bearbeiten', 'ecf-framework' ); ?>"><?php echo esc_html( $chex ); ?></span>
                <button type="button" class="v2-edit-btn" onclick="event.stopPropagation();ecfV2ToggleEdit('<?php echo esc_js( $cname ); ?>')" title="<?php esc_attr_e( 'Token bearbeiten', 'ecf-framework' ); ?>" aria-label="<?php esc_attr_e( 'Token bearbeiten', 'ecf-framework' ); ?>">
                  <svg width="11" height="11" viewBox="0 0 13 13" fill="none"><path d="M8.5 2L11 4.5 5 10.5H2.5V8L8.5 2z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="v2-edit-btn v2-edit-btn--danger" onclick="event.stopPropagation();var _t=this.closest('.v2-tr');if(_t){_t.remove();ecfV2ScheduleAutosave();}" title="<?php esc_attr_e( 'Remove', 'ecf-framework' ); ?>" aria-label="<?php esc_attr_e( 'Remove', 'ecf-framework' ); ?>">×</button>
              </div>
            </div>
            <?php if ( ! empty( $cshade_dat['shades'] ) || ! empty( $cshade_dat['tints'] ) ) : ?>
            <div class="v2-shade-strip">
              <?php if ( ! empty( $cshade_dat['shades'] ) ) : ?>
              <div class="v2-shade-strip-row">
                <?php foreach ( $cshade_dat['shades'] as $shade_hex ) : ?>
                <div class="v2-shade-sw" style="background:<?php echo esc_attr( $shade_hex ); ?>" title="<?php echo esc_attr( $shade_hex ); ?>"></div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              <?php if ( ! empty( $cshade_dat['tints'] ) ) : ?>
              <div class="v2-shade-strip-row">
                <?php foreach ( $cshade_dat['tints'] as $shade_hex ) : ?>
                <div class="v2-shade-sw" style="background:<?php echo esc_attr( $shade_hex ); ?>" title="<?php echo esc_attr( $shade_hex ); ?>"></div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            <!-- Edit panel -->
            <div class="v2-tr-edit" id="v2-edit-<?php echo esc_attr( $cname ); ?>">
              <div class="v2-color-edit-panel">
                <div class="v2-color-edit-pickers">
                  <input type="color"
                         class="v2-color-native"
                         id="v2-cp-<?php echo esc_attr( $cname ); ?>"
                         value="<?php echo esc_attr( $chex ); ?>"
                         data-v2-color-id="<?php echo esc_attr( $cname ); ?>"
                         title="<?php esc_attr_e( 'Color picker', 'ecf-framework' ); ?>">
                  <div class="v2-color-edit-right">
                    <div class="v2-color-edit-preview" id="v2-esw-<?php echo esc_attr( $cname ); ?>" style="background:<?php echo esc_attr( $chex ); ?>"></div>
                    <input type="text"
                           class="v2-si v2-color-hex-inp"
                           id="v2-einp-<?php echo esc_attr( $cname ); ?>"
                           value="<?php echo esc_attr( $chex ); ?>"
                           placeholder="#000000"
                           data-v2-color-id="<?php echo esc_attr( $cname ); ?>"
                           maxlength="7">
                  </div>
                </div>
                <div class="v2-edit-var-label">--ecf-color-<span class="v2-evar-name"><?php echo esc_html( $cname ); ?></span></div>
                <!-- Format selector -->
                <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                  <label class="v2-mini-label"><?php esc_html_e( 'Format', 'ecf-framework' ); ?></label>
                  <select class="v2-si v2-si--sm v2-select" name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo $i; ?>][format]" style="max-width:90px">
                    <?php foreach ( [ 'hex' => 'HEX', 'rgb' => 'RGB', 'rgba' => 'RGBA', 'hsl' => 'HSL', 'hsla' => 'HSLA' ] as $fv => $fl ) : ?>
                    <option value="<?php echo esc_attr( $fv ); ?>" <?php selected( $cfmt, $fv ); ?>><?php echo esc_html( $fl ); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <!-- Shade / Tint generator -->
                <?php
                $gen_shades  = ! empty( $row['generate_shades'] );
                $shade_cnt   = (int) ( $row['shade_count']   ?? 4 );
                $gen_tints   = ! empty( $row['generate_tints'] );
                $tint_cnt    = (int) ( $row['tint_count']    ?? 4 );
                ?>
                <div class="v2-shade-controls" style="margin-top:10px;display:flex;flex-direction:column;gap:7px">
                  <!-- Schattierungen -->
                  <div class="v2-shade-row">
                    <label class="v2-shade-label">
                      <input type="checkbox"
                             class="v2-shade-cb"
                             data-shade-target="v2-sc-<?php echo esc_attr( $cname ); ?>"
                             name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo $i; ?>][generate_shades]"
                             value="1"
                             <?php checked( $gen_shades ); ?>>
                      <span><?php esc_html_e( 'Schattierungen erzeugen', 'ecf-framework' ); ?></span>
                    </label>
                    <div class="v2-stepper <?php echo $gen_shades ? '' : 'v2-stepper--off'; ?>" id="v2-sc-<?php echo esc_attr( $cname ); ?>">
                      <button type="button" class="v2-stepper-btn" data-stepper-target="v2-sc-inp-<?php echo esc_attr( $cname ); ?>" data-stepper-delta="-1">−</button>
                      <input type="number" class="v2-stepper-inp" id="v2-sc-inp-<?php echo esc_attr( $cname ); ?>"
                             name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo $i; ?>][shade_count]"
                             value="<?php echo esc_attr( $shade_cnt ); ?>" min="1" max="10">
                      <button type="button" class="v2-stepper-btn" data-stepper-target="v2-sc-inp-<?php echo esc_attr( $cname ); ?>" data-stepper-delta="1">+</button>
                    </div>
                  </div>
                  <!-- Aufhellungen -->
                  <div class="v2-shade-row">
                    <label class="v2-shade-label">
                      <input type="checkbox"
                             class="v2-shade-cb"
                             data-shade-target="v2-tc-<?php echo esc_attr( $cname ); ?>"
                             name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo $i; ?>][generate_tints]"
                             value="1"
                             <?php checked( $gen_tints ); ?>>
                      <span><?php esc_html_e( 'Aufhellungen erzeugen', 'ecf-framework' ); ?></span>
                    </label>
                    <div class="v2-stepper <?php echo $gen_tints ? '' : 'v2-stepper--off'; ?>" id="v2-tc-<?php echo esc_attr( $cname ); ?>">
                      <button type="button" class="v2-stepper-btn" data-stepper-target="v2-tc-inp-<?php echo esc_attr( $cname ); ?>" data-stepper-delta="-1">−</button>
                      <input type="number" class="v2-stepper-inp" id="v2-tc-inp-<?php echo esc_attr( $cname ); ?>"
                             name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo $i; ?>][tint_count]"
                             value="<?php echo esc_attr( $tint_cnt ); ?>" min="1" max="10">
                      <button type="button" class="v2-stepper-btn" data-stepper-target="v2-tc-inp-<?php echo esc_attr( $cname ); ?>" data-stepper-delta="1">+</button>
                    </div>
                  </div>
                </div>
                <!-- Dark Mode Override -->
                <?php $dark_val = $row['dark_value'] ?? ''; $dark_enabled = ! empty( $row['dark_enabled'] ); ?>
                <details class="v2-dark-toggle" <?php echo $dark_enabled ? 'open' : ''; ?>>
                  <summary>🌙 <?php esc_html_e( 'Dark Mode Variante', 'ecf-framework' ); ?></summary>
                  <div class="v2-dark-toggle-body">
                    <label class="v2-shade-label" style="margin-bottom:6px">
                      <input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo $i; ?>][dark_enabled]" value="1" <?php checked( $dark_enabled ); ?> class="v2-dark-cb">
                      <span><?php esc_html_e( 'Dark Mode Wert aktiv', 'ecf-framework' ); ?></span>
                    </label>
                    <div style="display:flex;align-items:center;gap:8px">
                      <input type="color" class="v2-color-native" value="<?php echo esc_attr( $dark_val ?: $chex ); ?>" data-v2-dark-color-id="<?php echo esc_attr( $cname ); ?>" style="width:36px;height:28px">
                      <input type="text" class="v2-si v2-color-hex-inp" name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo $i; ?>][dark_value]" value="<?php echo esc_attr( $dark_val ); ?>" placeholder="#000000" maxlength="7" data-v2-dark-hex-id="<?php echo esc_attr( $cname ); ?>" style="width:90px">
                      <span style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3)">@media (prefers-color-scheme: dark)</span>
                    </div>
                  </div>
                </details>
                <!-- Hidden form inputs — serialised by ecfV2Save() -->
                <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo $i; ?>][name]"  value="<?php echo esc_attr( $row['name'] ?? $cname ); ?>">
                <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo $i; ?>][value]" value="<?php echo esc_attr( $cval ); ?>" id="v2-val-<?php echo esc_attr( $cname ); ?>">
                <div class="v2-color-edit-actions">
                  <button type="button" class="v2-btn v2-btn--primary" onclick="ecfV2ApplyColor('<?php echo esc_js( $cname ); ?>')"><?php esc_html_e( 'Apply', 'ecf-framework' ); ?></button>
                  <button type="button" class="v2-btn v2-btn--ghost"   onclick="ecfV2ToggleEdit('<?php echo esc_js( $cname ); ?>')"><?php esc_html_e( 'Cancel', 'ecf-framework' ); ?></button>
                  <button type="button" class="v2-btn v2-btn--ghost" style="margin-left:auto;color:var(--v2-text3)" data-v2-remove-row="color" data-v2-row-index="<?php echo esc_attr( $i ); ?>">✕ <?php esc_html_e( 'Remove', 'ecf-framework' ); ?></button>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <div style="padding:8px 4px 0">
            <button type="button" class="v2-btn v2-btn--ghost" data-v2-add-row="color" style="width:100%;justify-content:center">+ <?php esc_html_e( 'Farbe hinzufügen', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div><!-- /Colors -->

      <?php
      /* Local helpers — same shape as the ones in the cookbook + settings
         pages, scoped here so the Basisfarben block below can render
         independently of where $tok / $token_label live elsewhere. */
      $tok_label_local = function ( $var ) {
          if ( strpos( $var, '--ecf-base-text-color' )       === 0 ) return __( 'Basis-Textfarbe',          'ecf-framework' );
          if ( strpos( $var, '--ecf-base-background-color' ) === 0 ) return __( 'Basis-Hintergrundfarbe',   'ecf-framework' );
          if ( strpos( $var, '--ecf-link-color' )            === 0 ) return __( 'Link-Farbe',               'ecf-framework' );
          if ( strpos( $var, '--ecf-focus-color' )           === 0 ) return __( 'Fokus-Farbe',              'ecf-framework' );
          if ( strpos( $var, '--ecf-focus-outline-width' )   === 0 ) return __( 'Fokus-Rahmen-Breite',      'ecf-framework' );
          if ( strpos( $var, '--ecf-focus-outline-offset' )  === 0 ) return __( 'Fokus-Rahmen-Abstand',     'ecf-framework' );
          return '';
      };
      $tok_local = function ( $vars = [] ) use ( $tok_label_local ) {
          $vars = array_filter( (array) $vars );
          if ( ! $vars ) {
              return;
          }
          ?>
          <div class="v2-tok">
              <span class="v2-tok-l"><?php esc_html_e( 'VAR', 'ecf-framework' ); ?></span>
              <?php foreach ( $vars as $v ) :
                  $label = $tok_label_local( $v );
              ?><code<?php if ( $label ) : ?> data-v2-tip="<?php echo esc_attr( $label ); ?>"<?php endif; ?>><?php echo esc_html( $v ); ?></code><?php endforeach; ?>
          </div>
          <?php
      };
      ?>

      <!-- Base Colors (Hintergrund/Link/Fokus). Jedes Feld bietet
           entweder Custom-Hex oder einen Palette-Token als Wert —
           wenn ein Token gewählt ist, wird var(--ecf-color-X) gespeichert
           und der Hex-Picker greyed out. -->
      <?php
        // Build palette-token options once (used by all base color rows).
        // Use the same German labels as the palette section above (Primär,
        // Sekundär, Akzent, Fläche, Text) — fall back to user's title or
        // ucfirst of the slug for custom palette tokens (accentmint etc).
        $bcf_label_map = [
          'primary'   => __( 'Primary',   'ecf-framework' ),
          'secondary' => __( 'Secondary', 'ecf-framework' ),
          'accent'    => __( 'Accent',    'ecf-framework' ),
          'surface'   => __( 'Surface',   'ecf-framework' ),
          'text'      => __( 'Text',      'ecf-framework' ),
        ];
        $palette_tokens = [];
        $palette_hexes  = [];
        foreach ( ( $settings['colors'] ?? [] ) as $crow ) {
          $cn = sanitize_key( $crow['name'] ?? '' );
          if ( $cn === '' ) continue;
          $label = $bcf_label_map[ $cn ] ?? ucfirst( $cn );
          $palette_tokens[ $cn ] = $label . ' — var(--ecf-color-' . $cn . ')';
          $palette_hexes[ $cn ]  = (string) ( $crow['value'] ?? '' );
        }
      ?>
      <script>window.ecfPaletteHexes = <?php echo wp_json_encode( $palette_hexes ); ?>;</script>
      <?php

        $base_color_field = function ( $field_key, $hex_default, $label, $tokens_to_show ) use ( $opt, $settings, $palette_tokens, $tok_local ) {
          $current = (string) ( $settings[ $field_key ] ?? '' );
          $is_var  = (bool) preg_match( '/^var\(\s*--ecf-color-([a-z0-9_-]+)\s*\)$/i', $current, $m );
          $current_var_token = $is_var ? sanitize_key( $m[1] ) : '';
          $current_hex = $is_var ? $hex_default : ( $current ?: $hex_default );
          ?>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php echo esc_html( $label ); ?></div>
              <?php $tok_local( $tokens_to_show ); ?>
            </div>
            <div class="v2-base-color-field" data-bcf>
              <select class="v2-si v2-si--sm" data-bcf-mode>
                <option value=""<?php selected( ! $is_var ); ?>><?php esc_html_e( 'Custom (Hex)', 'ecf-framework' ); ?></option>
                <?php foreach ( $palette_tokens as $tok_key => $tok_label ) : ?>
                  <option value="<?php echo esc_attr( $tok_key ); ?>"<?php selected( $current_var_token, $tok_key ); ?>><?php echo esc_html( $tok_label ); ?></option>
                <?php endforeach; ?>
              </select>
              <input type="color" class="v2-si v2-si--color" data-bcf-hex value="<?php echo esc_attr( $current_hex ); ?>"<?php echo $is_var ? ' disabled' : ''; ?> style="<?php echo $is_var ? 'opacity:.4;cursor:not-allowed' : ''; ?>">
              <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[<?php echo esc_attr( $field_key ); ?>]" data-bcf-store value="<?php echo esc_attr( $current ?: $hex_default ); ?>">
            </div>
          </div>
          <?php
        };
      ?>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Basisfarben', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <?php $base_color_field( 'base_background_color', '#f8fafc', __( 'Hintergrundfarbe', 'ecf-framework' ), [ '--ecf-base-background-color' ] ); ?>
          <?php $base_color_field( 'link_color',           '#4f46e5', __( 'Link-Farbe',       'ecf-framework' ), [ '--ecf-link-color' ] ); ?>
          <?php $base_color_field( 'focus_color',          '#0ea5e9', __( 'Fokus-Farbe',      'ecf-framework' ), [ '--ecf-focus-color', '--ecf-focus-outline-width', '--ecf-focus-outline-offset' ] ); ?>
        </div>
      </div>
      <script>
      // Token <> Hex toggle for Basisfarben fields. The hidden input carries
      // the actual saved value: either a hex string or var(--ecf-color-X).
      // Token preview hex comes from window.ecfPaletteHexes (PHP-emitted)
      // since :root CSS vars don't exist in wp-admin context.
      (function() {
        function getTokenHex(token) {
          var raw = (window.ecfPaletteHexes && window.ecfPaletteHexes[token]) || '';
          raw = String(raw).trim();
          if (!raw) return '';
          if (/^#[0-9a-f]{3,8}$/i.test(raw)) return raw;
          // Convert rgb()/rgba()/hsl() to hex by routing through a temp element.
          var tmp = document.createElement('div');
          tmp.style.color = raw;
          document.body.appendChild(tmp);
          var rgb = getComputedStyle(tmp).color;
          document.body.removeChild(tmp);
          var m = rgb.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
          if (!m) return '';
          var hex = '#';
          for (var i = 1; i <= 3; i++) {
            var c = parseInt(m[i], 10).toString(16);
            hex += c.length === 1 ? '0' + c : c;
          }
          return hex;
        }
        document.querySelectorAll('[data-bcf]').forEach(function(wrap) {
          var sel  = wrap.querySelector('[data-bcf-mode]');
          var hex  = wrap.querySelector('[data-bcf-hex]');
          var hide = wrap.querySelector('[data-bcf-store]');
          if (!sel || !hex || !hide) return;
          function sync() {
            var token = sel.value;
            if (token) {
              hide.value = 'var(--ecf-color-' + token + ')';
              var resolved = getTokenHex(token);
              if (resolved) hex.value = resolved;
              hex.disabled = true; hex.style.opacity = '.7'; hex.style.cursor = 'not-allowed';
            } else {
              hide.value = hex.value;
              hex.disabled = false; hex.style.opacity = ''; hex.style.cursor = '';
            }
          }
          sel.addEventListener('change', sync);
          hex.addEventListener('input', function() { if (!sel.value) hide.value = hex.value; });
          // Run once on initial load so saved-token rows show their hex preview.
          if (sel.value) sync();
        });
      })();
      </script>

    </div><!-- /content -->

    <aside class="v2-aside" id="v2-color-aside">
      <div class="v2-as-head"><?php esc_html_e( 'Ausgewählte Farbe', 'ecf-framework' ); ?></div>
      <div class="v2-aside-swatch" id="v2-cp-main" data-active-id="<?php echo esc_attr( array_key_first( $c ) ?: 'primary' ); ?>" style="background:<?php echo esc_attr( reset( $c ) ?: '#3b82f6' ); ?>;cursor:pointer" title="<?php esc_attr_e( 'Click to edit', 'ecf-framework' ); ?>"></div>
      <div class="v2-as-row"><span class="v2-as-k">Token</span><span class="v2-as-v" id="v2-cp-label"><?php $first_key = array_key_first( $c ) ?: 'primary'; echo esc_html( $color_de_labels[ $first_key ] ?? $first_key ); ?></span></div>
      <div class="v2-as-block">
        <div class="v2-as-head"><?php esc_html_e( 'Alle Farben', 'ecf-framework' ); ?></div>
        <?php foreach ( $c as $cname => $chex ) : ?>
        <div class="v2-as-row">
          <span class="v2-as-k"><?php echo esc_html( $color_de_labels[ $cname ] ?? $cname ); ?></span>
          <span class="v2-as-v" style="display:flex;align-items:center;gap:5px">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:<?php echo esc_attr( $chex ); ?>"></span>
            <code style="font-size:var(--v2-btn-fs, 12px)"><?php echo esc_html( $chex ); ?></code>
          </span>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="v2-as-block">
        <div class="v2-as-head"><?php esc_html_e( 'Kontrast-Checker', 'ecf-framework' ); ?></div>
        <div class="v2-cc" id="v2-contrast-checker">
          <div class="v2-cc-row">
            <span class="v2-cc-lbl"><?php esc_html_e( 'Vordergrund', 'ecf-framework' ); ?></span>
            <select id="v2-cc-fg" class="v2-cc-sel">
              <option value=""><?php esc_html_e( '— Farbe —', 'ecf-framework' ); ?></option>
              <?php foreach ( $c as $cname => $chex ) : ?>
              <option value="<?php echo esc_attr( $chex ); ?>"><?php echo esc_html( $color_de_labels[ $cname ] ?? $cname ); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="color" id="v2-cc-fg-c" class="v2-cc-swatch" value="#ffffff">
          </div>
          <div class="v2-cc-row">
            <span class="v2-cc-lbl"><?php esc_html_e( 'Hintergrund', 'ecf-framework' ); ?></span>
            <select id="v2-cc-bg" class="v2-cc-sel">
              <option value=""><?php esc_html_e( '— Farbe —', 'ecf-framework' ); ?></option>
              <?php foreach ( $c as $cname => $chex ) : ?>
              <option value="<?php echo esc_attr( $chex ); ?>"><?php echo esc_html( $color_de_labels[ $cname ] ?? $cname ); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="color" id="v2-cc-bg-c" class="v2-cc-swatch" value="#09090b">
          </div>
          <div id="v2-cc-result" class="v2-cc-result" style="display:none">
            <div id="v2-cc-preview" class="v2-cc-preview">Aa</div>
            <div class="v2-cc-stats">
              <span id="v2-cc-ratio" class="v2-cc-ratio"></span>
              <span id="v2-cc-badge" class="v2-cc-badge"></span>
            </div>
          </div>
        </div>
      </div>
    </aside>
  </div>
</div><!-- /colors page -->

<!-- ═══ PAGE: RADIUS ════════════════════════════════════════════════ -->
<div id="ecf-v2-page-radius" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Radius', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <div class="v2-actions-menu">
        <button type="button" class="v2-btn v2-btn--primary v2-actions-toggle" data-v2-actions-toggle aria-haspopup="menu" aria-expanded="false" title="<?php esc_attr_e( 'Weitere Aktionen', 'ecf-framework' ); ?>"><span class="v2-actions-toggle__label"><?php esc_html_e( 'Aktionen', 'ecf-framework' ); ?></span><span class="v2-actions-toggle__chevron" aria-hidden="true">▾</span></button>
        <div class="v2-actions-menu__dropdown" role="menu" hidden>
          <button type="button" role="menuitem" class="v2-actions-menu__item v2-actions-menu__item--danger" data-v2-reset-defaults title="<?php esc_attr_e( 'Reset ALL settings to plugin defaults', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">↺</span><span><?php esc_html_e( 'Auf Grundeinstellungen zurücksetzen', 'ecf-framework' ); ?></span></button>
          <button type="button" role="menuitem" class="v2-actions-menu__item" data-v2-reset title="<?php esc_attr_e( 'Discard unsaved changes and reload page', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">✕</span><span><?php esc_html_e( 'Änderungen verwerfen', 'ecf-framework' ); ?></span></button>
        </div>
      </div>
      <button type="button" class="v2-btn v2-btn--outline" data-v2-save><?php esc_html_e( 'Speichern', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Radius', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Wie rund sind die Ecken von Buttons, Karten und Feldern? Kleine Werte = spitze Ecken, große Werte = runde Ecken.', 'ecf-framework' ); ?> <span title="<?php esc_attr_e( 'clamp(MIN, FLUID, MAX) ist eine CSS-Funktion die einen Wert zwischen Minimum und Maximum fließend skaliert — abhängig von der Viewport-Breite. So bleibt der Radius auf kleinen Screens kompakter und wächst proportional auf großen Screens.', 'ecf-framework' ); ?>" style="font-size:var(--v2-btn-fs, 12px);color:var(--v2-text3);border:1px solid var(--v2-border);border-radius:50%;padding:0 4px;cursor:help;vertical-align:middle">?</span></p></div>

      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Abrundungen', 'ecf-framework' ); ?></div>
        <div class="v2-tl" data-v2-tl-radius>
          <?php
          $radius_de_labels = [
            'xs'   => __( 'Extra Klein', 'ecf-framework' ),
            's'    => __( 'Klein',       'ecf-framework' ),
            'm'    => __( 'Mittel',      'ecf-framework' ),
            'l'    => __( 'Groß',        'ecf-framework' ),
            'xl'   => __( 'Extra Groß',  'ecf-framework' ),
            'full' => __( 'Rund',        'ecf-framework' ),
          ];
          foreach ( $radius_arr as $ri => $row ) :
            $rname    = sanitize_key( $row['name'] ?? '' );
            $rmin     = $row['min'] ?? '4px';
            $rmax     = $row['max'] ?? '4px';
            $rde_lbl  = $radius_de_labels[ $rname ] ?? $rname;
            if ( ! $rname ) continue;
          ?>
          <div class="v2-tr v2-tr--radius">
            <div class="v2-tr-main">
              <div class="v2-tr-sw v2-tr-sw--radius" style="border-radius:<?php echo esc_attr( $rmin ); ?>"></div>
              <div>
                <div class="v2-tr-name"><?php echo esc_html( $rde_lbl ); ?></div>
                <div class="v2-tr-var">--ecf-radius-<?php echo esc_html( $rname ); ?></div>
              </div>
              <div class="v2-tr-meta v2-tr-meta--radius">
                <label class="v2-mini-label"><?php esc_html_e( 'Min', 'ecf-framework' ); ?></label>
                <input type="text" class="v2-si v2-si--sm"
                       name="<?php echo esc_attr( $opt ); ?>[radius][<?php echo $ri; ?>][min]"
                       value="<?php echo esc_attr( $rmin ); ?>"
                       placeholder="4px" style="width:62px">
                <label class="v2-mini-label"><?php esc_html_e( 'Max', 'ecf-framework' ); ?></label>
                <input type="text" class="v2-si v2-si--sm"
                       name="<?php echo esc_attr( $opt ); ?>[radius][<?php echo $ri; ?>][max]"
                       value="<?php echo esc_attr( $rmax ); ?>"
                       placeholder="4px" style="width:62px">
                <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[radius][<?php echo $ri; ?>][name]" value="<?php echo esc_attr( $rname ); ?>">
                <button type="button" class="v2-edit-btn" data-v2-remove-row="radius" data-v2-row-index="<?php echo esc_attr( $ri ); ?>" title="<?php esc_attr_e( 'Remove', 'ecf-framework' ); ?>" style="margin-left:4px">✕</button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <div style="padding:8px 4px 0">
            <button type="button" class="v2-btn v2-btn--ghost" data-v2-add-row="radius" style="width:100%;justify-content:center">+ <?php esc_html_e( 'Abrundung hinzufügen', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>

    </div><!-- /content -->
  </div>
</div><!-- /radius page -->

<!-- ═══ PAGE: TYPOGRAFIE ════════════════════════════════════════════ -->
<div id="ecf-v2-page-typography" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Typography', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <div class="v2-actions-menu">
        <button type="button" class="v2-btn v2-btn--primary v2-actions-toggle" data-v2-actions-toggle aria-haspopup="menu" aria-expanded="false" title="<?php esc_attr_e( 'Weitere Aktionen', 'ecf-framework' ); ?>"><span class="v2-actions-toggle__label"><?php esc_html_e( 'Aktionen', 'ecf-framework' ); ?></span><span class="v2-actions-toggle__chevron" aria-hidden="true">▾</span></button>
        <div class="v2-actions-menu__dropdown" role="menu" hidden>
          <button type="button" role="menuitem" class="v2-actions-menu__item v2-actions-menu__item--danger" data-v2-reset-defaults title="<?php esc_attr_e( 'Reset ALL settings to plugin defaults', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">↺</span><span><?php esc_html_e( 'Auf Grundeinstellungen zurücksetzen', 'ecf-framework' ); ?></span></button>
          <button type="button" role="menuitem" class="v2-actions-menu__item" data-v2-reset title="<?php esc_attr_e( 'Discard unsaved changes and reload page', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">✕</span><span><?php esc_html_e( 'Änderungen verwerfen', 'ecf-framework' ); ?></span></button>
        </div>
      </div>
      <button type="button" class="v2-btn v2-btn--outline" data-v2-save><?php esc_html_e( 'Speichern', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Typografie', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Schriftfamilien, flüssige Schriftskalierung, Stärken und Zeilenhöhen als Design-Tokens.', 'ecf-framework' ); ?></p></div>
      <div class="v2-tabs">
        <button type="button" class="v2-tab v2-tab--on" data-v2-tab-group="ty" data-v2-tab="fonts"><?php esc_html_e( 'Schriften', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="ty" data-v2-tab="scale"><?php esc_html_e( 'Schriftgrößen', 'ecf-framework' ); ?><span class="v2-tc"><?php echo $typo_steps; ?></span></button>

        <button type="button" class="v2-tab" data-v2-tab-group="ty" data-v2-tab="pairings"><?php esc_html_e( 'Schriftpaare', 'ecf-framework' ); ?><span class="v2-tc">20</span></button>
      </div>

      <!-- Fonts tab -->
      <div id="v2-ty-fonts" class="v2-tp v2-tp--on">
        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Schriftfamilien', 'ecf-framework' ); ?></div>
          <div class="v2-tl v2-tl--ty">
            <?php
            $font_de_labels = [
              'primary'   => __( 'Body text',   'ecf-framework' ),
              'secondary' => __( 'Headings', 'ecf-framework' ),
              'mono'      => __( 'Mono',      'ecf-framework' ),
            ];
            foreach ( $fonts_arr as $fi => $frow ) :
              $fname    = sanitize_key( $frow['name'] ?? '' );
              $fval     = $frow['value'] ?? '';
              $fde_lbl  = $font_de_labels[ $fname ] ?? ucfirst( $fname );
              if ( ! $fname ) continue;
            ?>
            <?php
              $ftarget  = $fname === 'secondary' ? 'heading' : 'body';
              $fweight  = $fname === 'secondary' ? '700' : ( $settings['base_body_font_weight'] ?? '400' );
            ?>
            <div class="v2-tr" id="v2-tr-font-<?php echo esc_attr( $fname ); ?>">
              <div class="v2-tr-main" onclick="ecfV2ToggleFontEdit('<?php echo esc_js( $fname ); ?>')" style="cursor:pointer">
                <div class="v2-tr-sw v2-tr-sw--font" style="font-family:<?php echo esc_attr( $fval ); ?>;font-size:19px;<?php echo $fname === 'secondary' ? 'font-style:italic' : 'font-weight:700'; ?>">Aa</div>
                <div style="flex:1;min-width:0">
                  <div style="display:flex;align-items:baseline;gap:5px;overflow:hidden">
                    <span class="v2-tr-var" style="flex-shrink:0"><?php echo esc_html( $fde_lbl ); ?></span>
                    <span class="v2-tr-name" id="v2-font-chip-<?php echo esc_attr( $fname ); ?>" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html( trim( strtok( $fval, ',' ), "' " ) ); ?></span>
                    <span class="v2-tr-var" style="flex-shrink:0;font-variant-numeric:tabular-nums"><?php echo esc_html( $fweight ); ?></span>
                  </div>
                  <div class="v2-tr-var" style="margin-top:2px">--ecf-font-<?php echo esc_html( $fname ); ?></div>
                </div>
              </div>
              <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[typography][fonts][<?php echo $fi; ?>][name]" value="<?php echo esc_attr( $frow['name'] ?? $fname ); ?>">
              <input type="hidden" id="v2-font-val-<?php echo esc_attr( $fname ); ?>" name="<?php echo esc_attr( $opt ); ?>[typography][fonts][<?php echo $fi; ?>][value]" value="<?php echo esc_attr( $fval ); ?>">
              <div class="v2-tr-edit" id="v2-edit-font-<?php echo esc_attr( $fname ); ?>">
                <div class="v2-color-edit-panel">
                  <!-- System Font Stacks -->
                  <div class="v2-ty-sys-stacks">
                    <span class="v2-ty-sys-lbl"><?php esc_html_e( 'System stacks', 'ecf-framework' ); ?></span>
                    <button type="button" class="v2-ty-sys-btn" data-font-name="<?php echo esc_attr($fname); ?>" data-stack="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif">System UI</button>
                    <button type="button" class="v2-ty-sys-btn" data-font-name="<?php echo esc_attr($fname); ?>" data-stack="Georgia, 'Times New Roman', serif">Georgia</button>
                    <button type="button" class="v2-ty-sys-btn" data-font-name="<?php echo esc_attr($fname); ?>" data-stack="'Courier New', Courier, monospace">Mono</button>
                  </div>
                  <!-- Font search -->
                  <input type="text" class="v2-si" id="v2-fi-search-<?php echo esc_attr( $fname ); ?>" placeholder="<?php esc_attr_e( 'Search Google Fonts…', 'ecf-framework' ); ?>" style="width:100%;margin-bottom:6px" autocomplete="off" oninput="ecfV2SearchFontInline(this.value,'<?php echo esc_js( $fname ); ?>')">
                  <div id="v2-fi-results-<?php echo esc_attr( $fname ); ?>" style="display:none;border:1px solid var(--v2-border);border-radius:7px;overflow:hidden;max-height:220px;overflow-y:auto"></div>
                  <input type="hidden" id="v2-font-stack-<?php echo esc_attr( $fname ); ?>" value="<?php echo esc_attr( $fval ); ?>">
                  <!-- Weight Slider -->
                  <?php if ( $fname !== 'mono' ) : ?>
                  <div class="v2-ty-weight-row">
                    <span class="v2-ty-weight-lbl"><?php esc_html_e( 'Weight', 'ecf-framework' ); ?></span>
                    <input type="range" class="v2-ty-weight-slider" id="v2-fw-slider-<?php echo esc_attr($fname); ?>"
                           min="100" max="900" step="100"
                           value="<?php echo esc_attr( $fname === 'primary' ? ($settings['base_body_font_weight'] ?? 400) : 700 ); ?>"
                           data-fname="<?php echo esc_attr($fname); ?>">
                    <span class="v2-ty-weight-val" id="v2-fw-val-<?php echo esc_attr($fname); ?>"><?php echo esc_html( $fname === 'primary' ? ($settings['base_body_font_weight'] ?? 400) : 700 ); ?></span>
                  </div>
                  <?php endif; ?>
                  <!-- Varianten-Hinweis -->
                  <div class="v2-ty-variants-hint" id="v2-ty-variants-<?php echo esc_attr($fname); ?>"></div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="v2-sec">
          <div class="v2-sh" style="display:flex;align-items:center;gap:8px">
            <?php esc_html_e( 'Vorschau', 'ecf-framework' ); ?>
            <button type="button" class="v2-ty-theme-btn" id="v2-ty-theme-btn" data-theme="dark">☾</button>
          </div>
          <div class="v2-font-preview" id="v2-font-preview-block">
            <p class="v2-fp-h" id="v2-fp-h" style="font-family:<?php echo esc_attr( $font_primary ); ?>"><?php esc_html_e( 'The quick fox jumps', 'ecf-framework' ); ?></p>
            <p class="v2-fp-body" id="v2-fp-body" style="font-family:<?php echo esc_attr( $font_primary ); ?>"><?php esc_html_e( 'Body text preview for the primary font family. This is how paragraphs will look on the finished website.', 'ecf-framework' ); ?></p>
            <p class="v2-fp-secondary" id="v2-fp-secondary" style="font-family:<?php echo esc_attr( $font_secondary ); ?>"><?php esc_html_e( 'Secondary: for quotes and editorial accents.', 'ecf-framework' ); ?></p>
          </div>

          <!-- Realistische Seiten-Vorschau -->
          <?php
          $pv_primary   = $c['primary']   ?? '#6366f1';
          $pv_secondary = $c['secondary'] ?? '#f163be';
          $pv_accent    = $c['accent']    ?? '#63c3f1';
          $pv_surface   = $c['surface']   ?? '#f4f4f8';
          $pv_text      = $c['text']      ?? '#0f0f18';
          $pv_style = "--ty-primary:{$pv_primary};--ty-secondary:{$pv_secondary};--ty-accent:{$pv_accent};--ty-surface:{$pv_surface};--ty-text:{$pv_text}";
          /* compute --ecf-text-3xl / 4xl from the live scale for the preview */
          $pv_type_scale  = $settings['typography']['scale'] ?? [];
          $pv_ts_steps    = is_array($pv_type_scale['steps'] ?? null) ? $pv_type_scale['steps'] : ['xs','s','m','l','xl','2xl','3xl','4xl'];
          $pv_root_px     = $this->get_root_font_base_px( $settings );
          foreach ( $this->build_type_scale_preview( $pv_type_scale + ['steps' => $pv_ts_steps, 'base_index' => ($pv_type_scale['base_index'] ?? 'm')], $pv_root_px ) as $pv_ts_item ) {
            if ( in_array( $pv_ts_item['step'] ?? '', ['2xl','3xl','4xl'], true ) ) {
              $pv_style .= ';--ecf-text-' . esc_attr($pv_ts_item['step']) . ':' . esc_attr($pv_ts_item['css_value']);
            }
          }
          ?>
          <?php
          $pv_fn_pri = trim( strtok( $font_primary,   ',' ), "' " );
          $pv_fn_sec = trim( strtok( $font_secondary ?: $font_primary, ',' ), "' " );
          $pv_w_body = $settings['base_body_font_weight'] ?? '400';
          function pv_tip( $role, $fname, $size, $weight, $var ) {
            return esc_attr( "{$role} · {$fname} · {$size} · {$weight} · {$var}" );
          }
          ?>
          <div class="v2-ty-page-pv" id="v2-ty-page-pv" style="<?php echo esc_attr($pv_style); ?>">
            <div class="v2-ty-pv-hero">
              <div class="v2-ty-pv-badge" id="v2-ty-pv-badge"><?php esc_html_e( 'New Feature', 'ecf-framework' ); ?></div>
              <h1 class="v2-ty-pv-h1" id="v2-ty-pv-h1"
                  style="font-family:<?php echo esc_attr($font_secondary ?: $font_primary); ?>"
                  data-ty-tip="<?php echo pv_tip('H1 · --ecf-font-secondary', $pv_fn_sec, '--ecf-text-4xl', '800', '--ecf-font-secondary'); ?>"><?php esc_html_e( 'Design speaks.', 'ecf-framework' ); ?></h1>
              <p class="v2-ty-pv-sub" id="v2-ty-pv-sub"
                 style="font-family:<?php echo esc_attr($font_primary); ?>"
                 data-ty-tip="<?php echo pv_tip('Subtext · --ecf-font-primary', $pv_fn_pri, '13px', $pv_w_body, '--ecf-font-primary'); ?>"><?php esc_html_e( 'Great typography makes your message clear, your brand memorable, and your readers happy.', 'ecf-framework' ); ?></p>
              <div class="v2-ty-pv-actions">
                <button type="button" class="v2-ty-pv-cta" id="v2-ty-pv-cta"
                        style="font-family:<?php echo esc_attr($font_primary); ?>"
                        data-ty-tip="<?php echo pv_tip('Button · --ecf-font-primary', $pv_fn_pri, '12px', '600', '--ty-primary'); ?>"><?php esc_html_e( 'Get Started', 'ecf-framework' ); ?></button>
                <button type="button" class="v2-ty-pv-ghost" id="v2-ty-pv-ghost"
                        style="font-family:<?php echo esc_attr($font_primary); ?>"
                        data-ty-tip="<?php echo pv_tip('Button Ghost · --ecf-font-primary', $pv_fn_pri, '12px', '600', '--ty-secondary'); ?>"><?php esc_html_e( 'Learn More', 'ecf-framework' ); ?></button>
              </div>
            </div>
            <div class="v2-ty-pv-article">
              <h2 class="v2-ty-pv-h2" id="v2-ty-pv-h2"
                  style="font-family:<?php echo esc_attr($font_secondary ?: $font_primary); ?>"
                  data-ty-tip="<?php echo pv_tip('H2 · --ecf-font-secondary', $pv_fn_sec, '--ecf-text-3xl', '700', '--ecf-font-secondary'); ?>"><?php esc_html_e( 'Why type matters', 'ecf-framework' ); ?></h2>
              <h3 class="v2-ty-pv-h3" id="v2-ty-pv-h3"
                  style="font-family:<?php echo esc_attr($font_secondary ?: $font_primary); ?>"
                  data-ty-tip="<?php echo pv_tip('H3 · --ecf-font-secondary', $pv_fn_sec, '--ecf-text-2xl', '600', '--ecf-font-secondary'); ?>"><?php esc_html_e( 'Hierarchy guides the eye', 'ecf-framework' ); ?></h3>
              <p class="v2-ty-pv-p" id="v2-ty-pv-p"
                 style="font-family:<?php echo esc_attr($font_primary); ?>"
                 data-ty-tip="<?php echo pv_tip('Body · --ecf-font-primary', $pv_fn_pri, '13px', $pv_w_body, '--ecf-font-primary'); ?>"><?php esc_html_e( 'Body copy shows readability at normal reading size. Good typography guides the reader through content with effortless clarity. Line length, spacing, and weight all contribute to how comfortable your text feels at a glance.', 'ecf-framework' ); ?></p>
              <blockquote class="v2-ty-pv-quote" id="v2-ty-pv-quote"
                          style="font-family:<?php echo esc_attr($font_secondary ?: $font_primary); ?>"
                          data-ty-tip="<?php echo pv_tip('Quote · --ecf-font-secondary', $pv_fn_sec, '13px', 'italic', '--ecf-font-secondary'); ?>"><?php esc_html_e( '"Typography is the craft of endowing human language with a durable visual form."', 'ecf-framework' ); ?></blockquote>
            </div>
          </div>
        </div>
        <!-- Uploaded fonts list -->
        <div class="v2-sec" id="v2-lf-section">
          <div class="v2-sh"><?php esc_html_e( 'Hochgeladene Schriften', 'ecf-framework' ); ?></div>
          <div id="v2-lf-list"><!-- JS renders rows here --></div>
        </div>

        <!-- Font upload -->
        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Schrift hinzufügen', 'ecf-framework' ); ?></div>
          <!-- Direct file upload -->
          <div class="v2-uf-drop" id="v2-uf-drop">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <span><?php esc_html_e( 'Schrift hier ablegen oder Datei wählen', 'ecf-framework' ); ?></span>
            <input type="file" id="v2-uf-file" accept=".ttf,.otf,.woff,.woff2" style="position:absolute;inset:0;opacity:0;cursor:pointer">
          </div>
          <!-- Or pick from Media Library -->
          <div style="display:flex;align-items:center;gap:8px;margin:8px 0">
            <div style="flex:1;height:1px;background:var(--v2-border)"></div>
            <span style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3)"><?php esc_html_e( 'oder aus Mediathek', 'ecf-framework' ); ?></span>
            <div style="flex:1;height:1px;background:var(--v2-border)"></div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
            <input type="text" class="v2-si" id="v2-uf-url" placeholder="<?php esc_attr_e( 'URL der Schriftdatei', 'ecf-framework' ); ?>" style="flex:1">
            <button type="button" class="v2-btn v2-btn--ghost" id="v2-uf-pick"><?php esc_html_e( 'Mediathek', 'ecf-framework' ); ?></button>
          </div>
          <div class="v2-sg v2-sg--compact2" style="margin-bottom:8px">
            <div class="v2-sr">
              <div><div class="v2-sl"><?php esc_html_e( 'Familienname', 'ecf-framework' ); ?></div></div>
              <input type="text" class="v2-si" id="v2-uf-family" placeholder="z. B. MeineMarke">
            </div>
            <div class="v2-sr">
              <div><div class="v2-sl"><?php esc_html_e( 'Stärke', 'ecf-framework' ); ?></div></div>
              <select class="v2-si" id="v2-uf-weight">
                <?php foreach ( [100,200,300,400,500,600,700,800,900] as $w ) : ?>
                <option value="<?php echo $w; ?>"<?php echo $w === 400 ? ' selected' : ''; ?>><?php echo $w; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="v2-sr">
              <div><div class="v2-sl"><?php esc_html_e( 'Stil', 'ecf-framework' ); ?></div></div>
              <select class="v2-si" id="v2-uf-style">
                <option value="normal"><?php esc_html_e( 'Normal', 'ecf-framework' ); ?></option>
                <option value="italic"><?php esc_html_e( 'Kursiv', 'ecf-framework' ); ?></option>
              </select>
            </div>
          </div>
          <button type="button" class="v2-btn v2-btn--primary" id="v2-uf-add" style="width:100%;justify-content:center"><?php esc_html_e( 'Schrift speichern', 'ecf-framework' ); ?></button>
          <div id="v2-uf-msg" style="display:none;margin-top:6px;font-size:12px"></div>
        </div>
      </div><!-- /fonts tab -->

      <!-- Skala tab -->
      <div id="v2-ty-scale" class="v2-tp">
        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Skalenparameter', 'ecf-framework' ); ?></div>
          <div class="v2-sg v2-sg--compact2">
            <div class="v2-sr">
              <div><div class="v2-sl"><?php esc_html_e( 'Min. Basis (px)', 'ecf-framework' ); ?></div></div>
              <div style="display:flex;align-items:center;gap:6px">
                <input type="number" class="v2-si" id="v2-sp-min-base" data-v2-scale-param="min_base" name="<?php echo esc_attr( $opt ); ?>[typography][scale][min_base]" value="<?php echo esc_attr( $typo_scale['min_base'] ?? 16 ); ?>" min="8" max="32" step="0.5">
                <span class="v2-ty-read-warn" id="v2-ty-read-warn-min" style="display:none" title="<?php esc_attr_e( 'Readability warning: body size below 14px', 'ecf-framework' ); ?>">⚠</span>
              </div>
            </div>
            <div class="v2-sr">
              <div><div class="v2-sl"><?php esc_html_e( 'Max. Basis (px)', 'ecf-framework' ); ?></div></div>
              <div style="display:flex;align-items:center;gap:6px">
                <input type="number" class="v2-si" id="v2-sp-max-base" data-v2-scale-param="max_base" name="<?php echo esc_attr( $opt ); ?>[typography][scale][max_base]" value="<?php echo esc_attr( $typo_scale['max_base'] ?? 18 ); ?>" min="8" max="40" step="0.5">
                <span class="v2-ty-read-warn" id="v2-ty-read-warn-max" style="display:none" title="<?php esc_attr_e( 'Readability warning: body size below 14px', 'ecf-framework' ); ?>">⚠</span>
              </div>
            </div>
            <?php
            $ratio_options = [
                '1.067' => __( '1.067 — Kleine Sekunde',   'ecf-framework' ),
                '1.125' => __( '1.125 — Große Sekunde',    'ecf-framework' ),
                '1.2'   => __( '1.2 — Kleine Terz',        'ecf-framework' ),
                '1.25'  => __( '1.25 — Große Terz',        'ecf-framework' ),
                '1.333' => __( '1.333 — Reine Quarte',     'ecf-framework' ),
                '1.414' => __( '1.414 — Tritonus',         'ecf-framework' ),
                '1.5'   => __( '1.5 — Reine Quinte',       'ecf-framework' ),
                '1.6'   => __( '1.6 — Kleine Sexte',       'ecf-framework' ),
                '1.618' => __( '1.618 — Goldener Schnitt', 'ecf-framework' ),
                '1.667' => __( '1.667 — Große Sexte',      'ecf-framework' ),
                '1.778' => __( '1.778 — Kleine Septime',   'ecf-framework' ),
                '1.875' => __( '1.875 — Große Septime',    'ecf-framework' ),
                '2'     => __( '2.0 — Oktave',             'ecf-framework' ),
            ];
            $cur_min_ratio = (string) ( $typo_scale['min_ratio'] ?? '1.125' );
            $cur_max_ratio = (string) ( $typo_scale['max_ratio'] ?? '1.25'  );
            ?>
            <div class="v2-sr">
              <div><div class="v2-sl"><?php esc_html_e( 'Kleiner Bildschirm (px)', 'ecf-framework' ); ?></div></div>
              <input type="number" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[typography][scale][min_vw]" value="<?php echo esc_attr( $typo_scale['min_vw'] ?? 375 ); ?>" min="280" max="800">
            </div>
            <div class="v2-sr">
              <div><div class="v2-sl"><?php esc_html_e( 'Großer Bildschirm (px)', 'ecf-framework' ); ?></div></div>
              <input type="number" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[typography][scale][max_vw]" value="<?php echo esc_attr( $typo_scale['max_vw'] ?? 1280 ); ?>" min="800" max="2560">
            </div>
            <div class="v2-sr">
              <div><div class="v2-sl"><?php esc_html_e( 'Min. Verhältnis', 'ecf-framework' ); ?></div></div>
              <select class="v2-si" id="v2-sp-min-ratio" data-v2-scale-param="min_ratio" name="<?php echo esc_attr( $opt ); ?>[typography][scale][min_ratio]">
                <?php foreach ( $ratio_options as $val => $label ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $cur_min_ratio, $val ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="v2-sr">
              <div><div class="v2-sl"><?php esc_html_e( 'Max. Verhältnis', 'ecf-framework' ); ?></div></div>
              <select class="v2-si" id="v2-sp-max-ratio" data-v2-scale-param="max_ratio" name="<?php echo esc_attr( $opt ); ?>[typography][scale][max_ratio]">
                <?php foreach ( $ratio_options as $val => $label ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $cur_max_ratio, $val ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- preserve other scale fields -->
            <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[typography][scale][base_index]" value="<?php echo esc_attr( $typo_scale['base_index'] ?? 'm' ); ?>">
            <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[typography][scale][fluid]"      value="1">
          </div>
        </div>
        <!-- Scale steps add/remove -->
        <div class="v2-sec">
          <div class="v2-sh" style="display:flex;align-items:center;justify-content:space-between">
            <?php esc_html_e( 'Skalenstufen', 'ecf-framework' ); ?>
            <span id="v2-ty-step-count-badge" style="font-size:var(--v2-ui-base-fs, 13px);font-weight:400;color:var(--v2-text3)"><?php echo esc_html( $typo_steps ); ?> <?php esc_html_e( 'Stufen', 'ecf-framework' ); ?></span>
          </div>
          <div id="v2-ty-steps-wrap">
            <?php
            $ty_steps_default = [ 'xs','s','m','l','xl','2xl','3xl','4xl' ];
            $ty_steps_arr = $typo_scale['steps'] ?? $ty_steps_default;
            foreach ( $ty_steps_arr as $ts ) :
            ?>
            <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[typography][scale][steps][]" class="v2-step-input" data-step-group="ty" value="<?php echo esc_attr( $ts ); ?>">
            <?php endforeach; ?>
          </div>
          <div style="display:flex;gap:6px;align-items:center">
            <button type="button" class="v2-btn v2-btn--ghost" data-v2-step-add="ty-smaller" title="<?php esc_attr_e( 'Stufe unterhalb der kleinsten Größe einfügen', 'ecf-framework' ); ?>">+ <?php esc_html_e( 'Stufe darunter', 'ecf-framework' ); ?></button>
            <div id="v2-ty-steps-list" style="display:flex;gap:4px;flex-wrap:wrap;flex:1">
              <?php foreach ( $ty_steps_arr as $ts ) : ?>
              <span class="v2-step-chip" data-step-group="ty" data-step-val="<?php echo esc_attr( $ts ); ?>" style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:999px;font-size:var(--v2-ui-base-fs, 13px);background:rgba(255,255,255,.07);cursor:pointer" onclick="ecfV2RemoveStep('ty','<?php echo esc_js( $ts ); ?>')" title="<?php esc_attr_e( 'Remove', 'ecf-framework' ); ?>"><?php echo esc_html( $ts ); ?> <span style="opacity:.5">×</span></span>
              <?php endforeach; ?>
            </div>
            <button type="button" class="v2-btn v2-btn--ghost" data-v2-step-add="ty-larger" title="<?php esc_attr_e( 'Stufe oberhalb der größten Größe einfügen', 'ecf-framework' ); ?>">+ <?php esc_html_e( 'Stufe darüber', 'ecf-framework' ); ?></button>
          </div>
        </div>
        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Skalenvorschau', 'ecf-framework' ); ?></div>
          <div class="v2-tl v2-tl--scroll" id="v2-scale-preview-tl" data-scale-base-index="<?php echo esc_attr( $typo_scale['base_index'] ?? 'm' ); ?>">
            <!-- Filled live by ecfV2UpdateScalePreview() -->
          </div>
        </div>
      </div><!-- /scale tab -->

      <!-- Stärken tab -->
      <!-- Pairings tab -->
      <div id="v2-ty-pairings" class="v2-tp">
        <?php
        $v2_pairings = [
            /* Tones mapped to 4 merged categories: modern, editorial, klassisch, neutral */
            [ 'slug'=>'modern-montserrat',  'tone'=>'modern',    'title'=>__('Montserrat & Open Sans','ecf-framework'),    'hf'=>'Montserrat',         'bf'=>'Open Sans',        'hs'=>__('The most trusted pair on the web','ecf-framework'),           'bs'=>__('Clean, reliable and familiar across every type of website.','ecf-framework') ],
            [ 'slug'=>'modern-poppins',     'tone'=>'modern',    'title'=>__('Poppins & Roboto','ecf-framework'),           'hf'=>'Poppins',            'bf'=>'Roboto',           'hs'=>__('Friendly, modern and instantly familiar','ecf-framework'),     'bs'=>__('Poppins brings personality while Roboto keeps body copy neutral.','ecf-framework') ],
            [ 'slug'=>'modern-systems',     'tone'=>'modern',    'title'=>__('Modern Systems','ecf-framework'),             'hf'=>'Space Grotesk',      'bf'=>'Source Sans 3',    'hs'=>__('A crisp system for modern product pages','ecf-framework'),     'bs'=>__('Balanced proportions for interfaces and product sites.','ecf-framework') ],
            [ 'slug'=>'modern-friendly',    'tone'=>'modern',    'title'=>__('Friendly Modern','ecf-framework'),            'hf'=>'Sora',               'bf'=>'Public Sans',      'hs'=>__('Modern without feeling cold or technical','ecf-framework'),    'bs'=>__('Warm and contemporary, works for interfaces and long content.','ecf-framework') ],
            [ 'slug'=>'neutral-interface',  'tone'=>'modern',    'title'=>__('Clean Interface','ecf-framework'),           'hf'=>'Albert Sans',        'bf'=>'DM Sans',          'hs'=>__('Simple, direct and easy to scan','ecf-framework'),              'bs'=>__('Two quiet geometric sans fonts for dashboards and SaaS.','ecf-framework') ],
            [ 'slug'=>'neutral-geometric',  'tone'=>'modern',    'title'=>__('Geometric Neutral','ecf-framework'),         'hf'=>'Jost',               'bf'=>'IBM Plex Sans',    'hs'=>__('Structured thinking made visible','ecf-framework'),             'bs'=>__('Geometric character with precise, readable body text.','ecf-framework') ],
            [ 'slug'=>'bold-agency',        'tone'=>'modern',    'title'=>__('Bold Agency','ecf-framework'),               'hf'=>'Oswald',             'bf'=>'Roboto',           'hs'=>__('Strong presence, zero compromise','ecf-framework'),             'bs'=>__('Condensed headlines command attention for agencies.','ecf-framework') ],
            [ 'slug'=>'bold-impact',        'tone'=>'modern',    'title'=>__('High Impact','ecf-framework'),               'hf'=>'Bebas Neue',         'bf'=>'Raleway',          'hs'=>__('Headlines that stop the scroll','ecf-framework'),               'bs'=>__('Extreme contrast between display and refined thin body.','ecf-framework') ],
            [ 'slug'=>'editorial-contrast', 'tone'=>'editorial', 'title'=>__('Editorial Contrast','ecf-framework'),        'hf'=>'DM Serif Display',   'bf'=>'Inter',            'hs'=>__('Design that feels premium at first glance','ecf-framework'),   'bs'=>__('Readable body copy keeps pages calm while the headline brings character.','ecf-framework') ],
            [ 'slug'=>'editorial-modern',   'tone'=>'editorial', 'title'=>__('Modern Editorial','ecf-framework'),          'hf'=>'Cormorant Garamond', 'bf'=>'Plus Jakarta Sans','hs'=>__('Story-first design with a modern edge','ecf-framework'),       'bs'=>__('Cultured and expressive while body copy stays crisp.','ecf-framework') ],
            [ 'slug'=>'editorial-spectral', 'tone'=>'editorial', 'title'=>__('Literary Elegance','ecf-framework'),         'hf'=>'Spectral',           'bf'=>'Karla',            'hs'=>__('Stories worth reading twice','ecf-framework'),                  'bs'=>__('Screen-optimised serif with a clean humanist body.','ecf-framework') ],
            [ 'slug'=>'warm-friendly',      'tone'=>'editorial', 'title'=>__('Friendly Service','ecf-framework'),          'hf'=>'Nunito',             'bf'=>'Open Sans',        'hs'=>__('Warm, open and easy to trust','ecf-framework'),                 'bs'=>__('Rounded letterforms feel personal and inviting.','ecf-framework') ],
            [ 'slug'=>'warm-studio',        'tone'=>'editorial', 'title'=>__('Soft Studio','ecf-framework'),               'hf'=>'Quicksand',          'bf'=>'Mulish',           'hs'=>__('Creative work with a human touch','ecf-framework'),             'bs'=>__('Soft curves feel handcrafted and approachable.','ecf-framework') ],
            [ 'slug'=>'classic-reader',     'tone'=>'klassisch', 'title'=>__('Classic Reader','ecf-framework'),            'hf'=>'Merriweather',       'bf'=>'Work Sans',        'hs'=>__('Built for articles, stories, and long reads','ecf-framework'),  'bs'=>__('Confident serif headlines with practical body text.','ecf-framework') ],
            [ 'slug'=>'classic-journal',    'tone'=>'klassisch', 'title'=>__('Classic Journal','ecf-framework'),           'hf'=>'Libre Baskerville',  'bf'=>'Lato',             'hs'=>__('A timeless reading rhythm for thoughtful content','ecf-framework'),'bs'=>__('Familiar, trustworthy and calm for long reads.','ecf-framework') ],
            [ 'slug'=>'classic-crimson',    'tone'=>'klassisch', 'title'=>__('Timeless Craft','ecf-framework'),            'hf'=>'Crimson Text',       'bf'=>'PT Sans',          'hs'=>__('Depth and warmth in every headline','ecf-framework'),           'bs'=>__('Old-world warmth with a highly readable body.','ecf-framework') ],
            [ 'slug'=>'calm-premium',       'tone'=>'klassisch', 'title'=>__('Calm Premium','ecf-framework'),              'hf'=>'Playfair Display',   'bf'=>'Manrope',          'hs'=>__('Quiet luxury without feeling distant','ecf-framework'),         'bs'=>__('Elegant headings with a contemporary, clear reading experience.','ecf-framework') ],
            [ 'slug'=>'premium-boutique',   'tone'=>'klassisch', 'title'=>__('Boutique Premium','ecf-framework'),          'hf'=>'Bodoni Moda',        'bf'=>'Outfit',           'hs'=>__('Elegant detail with clear modern support','ecf-framework'),    'bs'=>__('Luxurious contrast with a grounded, scannable body.','ecf-framework') ],
            [ 'slug'=>'premium-josefin',    'tone'=>'klassisch', 'title'=>__('Art Deco','ecf-framework'),                  'hf'=>'Josefin Sans',       'bf'=>'Crimson Pro',      'hs'=>__('Elegant geometry meets refined tradition','ecf-framework'),     'bs'=>__('Wide uppercase spacing with a beautiful serif body.','ecf-framework') ],
            [ 'slug'=>'editorial-josefin',  'tone'=>'klassisch', 'title'=>__('Josefin & Crimson Pro','ecf-framework'),     'hf'=>'Josefin Sans',       'bf'=>'Crimson Pro',      'hs'=>__('Elegant structure with literary warmth','ecf-framework'),       'bs'=>__('Art-deco geometry paired with a sophisticated serif.','ecf-framework') ],
        ];
        $v2_filter_tabs = [
            'all'       => __( 'Alle', 'ecf-framework' ),
            'modern'    => __( 'Serifenlos', 'ecf-framework' ),
            'editorial' => __( 'Serif-Mix', 'ecf-framework' ),
            'klassisch' => __( 'Antiqua', 'ecf-framework' ),
        ];
        ?>
        <div class="v2-sec">
          <div class="v2-tabs v2-tabs--inner" style="margin-bottom:14px">
            <?php foreach ( $v2_filter_tabs as $tkey => $tlabel ) : ?>
            <button type="button" class="v2-tab<?php echo $tkey === 'all' ? ' v2-tab--on' : ''; ?>" data-v2-pair-filter="<?php echo esc_attr( $tkey ); ?>"><?php echo esc_html( $tlabel ); ?><?php if ( $tkey === 'all' ) echo '<span class="v2-tc">' . count( $v2_pairings ) . '</span>'; ?></button>
            <?php endforeach; ?>
          </div>
          <div class="v2-fp-grid" id="v2-pair-grid">
            <?php foreach ( $v2_pairings as $p ) : ?>
            <div class="v2-fp-card" data-category="<?php echo esc_attr( $p['tone'] ); ?>">
              <div class="v2-fp-card-preview">
                <div class="v2-fp-card-head" style="font-family:'<?php echo esc_attr( $p['hf'] ); ?>',serif"><?php echo esc_html( $p['hs'] ); ?></div>
                <div class="v2-fp-card-sub" style="font-family:'<?php echo esc_attr( $p['bf'] ); ?>',sans-serif"><?php echo esc_html( $p['bs'] ); ?></div>
                <div class="v2-fp-card-body" style="font-family:'<?php echo esc_attr( $p['bf'] ); ?>',sans-serif"><?php esc_html_e( 'Body text shows how readable this combination feels for longer paragraphs and everyday content.', 'ecf-framework' ); ?></div>
              </div>
              <div class="v2-fp-card-foot">
                <div class="v2-fp-card-meta">
                  <div class="v2-fp-card-meta-row">
                    <span class="v2-fp-card-meta-lbl"><?php esc_html_e( 'H', 'ecf-framework' ); ?></span>
                    <span class="v2-fp-card-meta-val" style="font-family:'<?php echo esc_attr( $p['hf'] ); ?>',serif"><?php echo esc_html( $p['hf'] ); ?></span>
                  </div>
                  <div class="v2-fp-card-meta-row">
                    <span class="v2-fp-card-meta-lbl"><?php esc_html_e( 'B', 'ecf-framework' ); ?></span>
                    <span class="v2-fp-card-meta-val" style="font-family:'<?php echo esc_attr( $p['bf'] ); ?>',sans-serif"><?php echo esc_html( $p['bf'] ); ?></span>
                  </div>
                </div>
                <button type="button" class="v2-btn v2-btn--ghost" style="padding:0 10px;height:26px;flex-shrink:0"
                        data-v2-apply-pairing
                        data-heading="<?php echo esc_attr( $p['hf'] ); ?>"
                        data-body="<?php echo esc_attr( $p['bf'] ); ?>">
                  <?php esc_html_e( 'Apply', 'ecf-framework' ); ?>
                </button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div><!-- /pairings tab -->

    </div><!-- /content -->
    <aside class="v2-aside" id="v2-ty-scale-aside">
      <div class="v2-as-head"><?php esc_html_e( 'Skalenparameter', 'ecf-framework' ); ?></div>
      <div class="v2-as-row"><span class="v2-as-k">Basis min</span><span class="v2-as-v"><?php echo esc_html( ( $typo_scale['min_base'] ?? '16' ) . 'px' ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Basis max</span><span class="v2-as-v"><?php echo esc_html( ( $typo_scale['max_base'] ?? '18' ) . 'px' ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Ratio min</span><span class="v2-as-v"><?php echo esc_html( $typo_scale['min_ratio'] ?? '1.125' ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Ratio max</span><span class="v2-as-v"><?php echo esc_html( $typo_scale['max_ratio'] ?? '1.25' ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Bildschirmbreite</span><span class="v2-as-v"><?php echo esc_html( ( $typo_scale['min_vw'] ?? '375' ) . '–' . ( $typo_scale['max_vw'] ?? '1280' ) ); ?></span></div>
      <div class="v2-as-block" id="v2-ty-scale-overview">
        <div class="v2-as-head"><?php esc_html_e( 'Skalenübersicht', 'ecf-framework' ); ?></div>
        <?php
        $all_scale_steps = $scale_for_calc['steps'] ?? [ 'xs','s','m','l','xl','2xl','3xl','4xl' ];
        $max_px_val = 1;
        foreach ( $all_scale_steps as $st ) {
            $px = $scale_steps[ $st ] ?? 0;
            if ( $px > $max_px_val ) $max_px_val = $px;
        }
        foreach ( $all_scale_steps as $st ) :
            $px  = $scale_steps[ $st ] ?? null;
            $pct = $px ? round( $px / $max_px_val * 100 ) : 0;
        ?>
        <div class="v2-sc-row"><span class="v2-sc-lbl"><?php echo esc_html( $st ); ?></span><div class="v2-sc-track"><div class="v2-sc-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div><span class="v2-sc-px"><?php echo $px !== null ? esc_html( $px ) . 'px' : '?px'; ?></span></div>
        <?php endforeach; ?>
      </div>
    </aside>
  </div>
</div><!-- /typography page -->

<!-- ═══ PAGE: ABSTÄNDE ══════════════════════════════════════════════ -->
<div id="ecf-v2-page-spacing" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Abstände', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <div class="v2-actions-menu">
        <button type="button" class="v2-btn v2-btn--primary v2-actions-toggle" data-v2-actions-toggle aria-haspopup="menu" aria-expanded="false" title="<?php esc_attr_e( 'Weitere Aktionen', 'ecf-framework' ); ?>"><span class="v2-actions-toggle__label"><?php esc_html_e( 'Aktionen', 'ecf-framework' ); ?></span><span class="v2-actions-toggle__chevron" aria-hidden="true">▾</span></button>
        <div class="v2-actions-menu__dropdown" role="menu" hidden>
          <button type="button" role="menuitem" class="v2-actions-menu__item v2-actions-menu__item--danger" data-v2-reset-defaults title="<?php esc_attr_e( 'Reset ALL settings to plugin defaults', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">↺</span><span><?php esc_html_e( 'Auf Grundeinstellungen zurücksetzen', 'ecf-framework' ); ?></span></button>
          <button type="button" role="menuitem" class="v2-actions-menu__item" data-v2-reset title="<?php esc_attr_e( 'Discard unsaved changes and reload page', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">✕</span><span><?php esc_html_e( 'Änderungen verwerfen', 'ecf-framework' ); ?></span></button>
        </div>
      </div>
      <button type="button" class="v2-btn v2-btn--outline" data-v2-save><?php esc_html_e( 'Speichern', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Abstände', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Abstände für Buttons, Zwischenräume zwischen Abschnitten und Innenabstände von Karten — auf kleinen und großen Bildschirmen automatisch angepasst.', 'ecf-framework' ); ?></p></div>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Skalierungsparameter', 'ecf-framework' ); ?></div>
        <p style="font-size:11.5px;color:var(--v2-text3);margin:0 0 10px;line-height:1.5"><?php esc_html_e( 'Min = Wert auf kleinen Bildschirmen · Max = Wert auf großen Bildschirmen. Der Abstand passt sich dazwischen automatisch an.', 'ecf-framework' ); ?></p>
        <div class="v2-sg v2-sg--compact2">
          <div class="v2-sr">
            <div><div class="v2-sl"><?php esc_html_e( 'Min. Basis (px)', 'ecf-framework' ); ?></div></div>
            <input type="number" class="v2-si" id="v2-spp-min-base" data-v2-sp-param="min_base" name="<?php echo esc_attr( $opt ); ?>[spacing][min_base]" value="<?php echo esc_attr( $sp_base_min ); ?>" min="4" max="40">
          </div>
          <div class="v2-sr">
            <div><div class="v2-sl"><?php esc_html_e( 'Max. Basis (px)', 'ecf-framework' ); ?></div></div>
            <input type="number" class="v2-si" id="v2-spp-max-base" data-v2-sp-param="max_base" name="<?php echo esc_attr( $opt ); ?>[spacing][max_base]" value="<?php echo esc_attr( $sp_base_max ); ?>" min="4" max="80">
          </div>
          <div class="v2-sr">
            <div><div class="v2-sl"><?php esc_html_e( 'Kleiner Bildschirm (px)', 'ecf-framework' ); ?></div></div>
            <input type="number" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[spacing][min_vw]" value="<?php echo esc_attr( $spacing['min_vw'] ?? '375' ); ?>" min="280" max="800">
          </div>
          <div class="v2-sr">
            <div><div class="v2-sl"><?php esc_html_e( 'Großer Bildschirm (px)', 'ecf-framework' ); ?></div></div>
            <input type="number" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[spacing][max_vw]" value="<?php echo esc_attr( $spacing['max_vw'] ?? '1280' ); ?>" min="800" max="2560">
          </div>
          <div class="v2-sr">
            <div><div class="v2-sl"><?php esc_html_e( 'Min. Verhältnis', 'ecf-framework' ); ?></div></div>
            <select class="v2-si" id="v2-spp-min-ratio" data-v2-sp-param="min_ratio" name="<?php echo esc_attr( $opt ); ?>[spacing][min_ratio]">
              <?php foreach ( $ratio_options as $val => $label ) : ?>
              <option value="<?php echo esc_attr( $val ); ?>"<?php selected( (string) $sp_ratio_min, $val ); ?>><?php echo esc_html( $label ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="v2-sr">
            <div><div class="v2-sl"><?php esc_html_e( 'Max. Verhältnis', 'ecf-framework' ); ?></div></div>
            <select class="v2-si" id="v2-spp-max-ratio" data-v2-sp-param="max_ratio" name="<?php echo esc_attr( $opt ); ?>[spacing][max_ratio]">
              <?php foreach ( $ratio_options as $val => $label ) : ?>
              <option value="<?php echo esc_attr( $val ); ?>"<?php selected( (string) $sp_ratio_max, $val ); ?>><?php echo esc_html( $label ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- preserve spacing meta fields -->
          <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[spacing][base_index]" value="<?php echo esc_attr( $spacing['base_index'] ?? 'm' ); ?>">
          <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[spacing][fluid]"      value="1">
          <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[spacing][prefix]"     value="<?php echo esc_attr( $spacing['prefix'] ?? 'space' ); ?>">
        </div>
      </div>
      <!-- Spacing steps add/remove -->
      <div class="v2-sec">
        <div class="v2-sh" style="display:flex;align-items:center;justify-content:space-between">
          <?php esc_html_e( 'Scale Steps', 'ecf-framework' ); ?>
          <span style="font-size:var(--v2-ui-base-fs, 13px);font-weight:400;color:var(--v2-text3)"><?php echo esc_html( $spacing_steps ); ?> <?php esc_html_e( 'Stufen', 'ecf-framework' ); ?></span>
        </div>
        <div id="v2-sp-steps-wrap">
          <?php
          $sp_steps_default = [ '3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl' ];
          $sp_steps_arr = $spacing['steps'] ?? $sp_steps_default;
          foreach ( $sp_steps_arr as $ss ) :
          ?>
          <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[spacing][steps][]" class="v2-step-input" data-step-group="sp" value="<?php echo esc_attr( $ss ); ?>">
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:6px;align-items:center">
          <button type="button" class="v2-btn v2-btn--ghost" data-v2-step-add="sp-smaller" title="<?php esc_attr_e( 'Stufe unterhalb des kleinsten Abstands einfügen', 'ecf-framework' ); ?>">+ <?php esc_html_e( 'Stufe darunter', 'ecf-framework' ); ?></button>
          <div id="v2-sp-steps-list" style="display:flex;gap:4px;flex-wrap:wrap;flex:1">
            <?php foreach ( $sp_steps_arr as $ss ) : ?>
            <span class="v2-step-chip" data-step-group="sp" data-step-val="<?php echo esc_attr( $ss ); ?>" style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:999px;font-size:var(--v2-ui-base-fs, 13px);background:rgba(255,255,255,.07);cursor:pointer" onclick="ecfV2RemoveStep('sp','<?php echo esc_js( $ss ); ?>')" title="<?php esc_attr_e( 'Remove', 'ecf-framework' ); ?>"><?php echo esc_html( $ss ); ?> <span style="opacity:.5">×</span></span>
            <?php endforeach; ?>
          </div>
          <button type="button" class="v2-btn v2-btn--ghost" data-v2-step-add="sp-larger" title="<?php esc_attr_e( 'Stufe oberhalb des größten Abstands einfügen', 'ecf-framework' ); ?>">+ <?php esc_html_e( 'Stufe darüber', 'ecf-framework' ); ?></button>
        </div>
      </div>
      <div class="v2-sec">
        <div class="v2-sh" id="v2-sp-preview-head"><?php echo esc_html( 'Scale · ' . $sp_base_min . '/' . $sp_base_max . 'px · Ratio ' . $sp_ratio_min . '/' . $sp_ratio_max ); ?></div>
        <div id="v2-sp-preview-list" class="v2-tl--scroll" style="display:flex;flex-direction:column;gap:1px"
             data-sp-base-index="<?php echo esc_attr( $spacing['base_index'] ?? 'm' ); ?>">
          <!-- Filled live by ecfV2UpdateSpacingPreview() -->
        </div>
      </div>
    </div>
    <aside class="v2-aside">
      <div class="v2-as-head"><?php esc_html_e( 'Current Scale', 'ecf-framework' ); ?></div>
      <div class="v2-as-row"><span class="v2-as-k">Prefix</span><span class="v2-as-v"><?php echo esc_html( $spacing['prefix'] ?? 'space' ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Basis min</span><span class="v2-as-v"><?php echo esc_html( $sp_base_min . 'px' ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Basis max</span><span class="v2-as-v"><?php echo esc_html( $sp_base_max . 'px' ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Ratio min</span><span class="v2-as-v"><?php echo esc_html( $sp_ratio_min ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Ratio max</span><span class="v2-as-v"><?php echo esc_html( $sp_ratio_max ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Steps</span><span class="v2-as-v"><?php echo esc_html( $spacing_steps ); ?></span></div>
      <div class="v2-as-block" id="v2-sp-scale-overview">
        <div class="v2-as-head"><?php esc_html_e( 'Skalenübersicht', 'ecf-framework' ); ?></div>
        <?php
        $sp_ov_steps   = $spacing['steps'] ?? [ '3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl' ];
        $sp_ov_base    = (float) $sp_base_min;
        $sp_ov_ratio   = (float) $sp_ratio_min;
        $sp_ov_base_idx = array_search( $spacing['base_index'] ?? 'm', $sp_ov_steps, true );
        if ( $sp_ov_base_idx === false ) $sp_ov_base_idx = (int) floor( count( $sp_ov_steps ) / 2 );
        $sp_ov_px = [];
        foreach ( $sp_ov_steps as $i => $st ) {
            $sp_ov_px[ $st ] = max( 1, round( $sp_ov_base * pow( $sp_ov_ratio, $i - $sp_ov_base_idx ) ) );
        }
        $sp_ov_max = max( array_values( $sp_ov_px ) ) ?: 1;
        foreach ( $sp_ov_steps as $st ) :
            $px  = $sp_ov_px[ $st ];
            $pct = round( $px / $sp_ov_max * 100 );
        ?>
        <div class="v2-sc-row"><span class="v2-sc-lbl"><?php echo esc_html( $st ); ?></span><div class="v2-sc-track"><div class="v2-sc-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div><span class="v2-sc-px"><?php echo esc_html( $px ); ?>px</span></div>
        <?php endforeach; ?>
      </div>
    </aside>
  </div>
</div><!-- /spacing page -->

<!-- ═══ PAGE: SCHATTEN ══════════════════════════════════════════════ -->
<div id="ecf-v2-page-shadows" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Shadows', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <div class="v2-actions-menu">
        <button type="button" class="v2-btn v2-btn--primary v2-actions-toggle" data-v2-actions-toggle aria-haspopup="menu" aria-expanded="false" title="<?php esc_attr_e( 'Weitere Aktionen', 'ecf-framework' ); ?>"><span class="v2-actions-toggle__label"><?php esc_html_e( 'Aktionen', 'ecf-framework' ); ?></span><span class="v2-actions-toggle__chevron" aria-hidden="true">▾</span></button>
        <div class="v2-actions-menu__dropdown" role="menu" hidden>
          <button type="button" role="menuitem" class="v2-actions-menu__item v2-actions-menu__item--danger" data-v2-reset-defaults title="<?php esc_attr_e( 'Reset ALL settings to plugin defaults', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">↺</span><span><?php esc_html_e( 'Auf Grundeinstellungen zurücksetzen', 'ecf-framework' ); ?></span></button>
          <button type="button" role="menuitem" class="v2-actions-menu__item" data-v2-reset title="<?php esc_attr_e( 'Discard unsaved changes and reload page', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">✕</span><span><?php esc_html_e( 'Änderungen verwerfen', 'ecf-framework' ); ?></span></button>
        </div>
      </div>
      <button type="button" class="v2-btn v2-btn--outline" data-v2-save><?php esc_html_e( 'Speichern', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Shadows', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Six shadow tokens — available as CSS variable and utility class. Click a row to preview, edit to change the value.', 'ecf-framework' ); ?></p></div>
      <?php
      /* Pre-split shadows into outer and inner groups */
      $sh_outer = [];
      $sh_inner = [];
      foreach ( $shadows_arr as $si => $srow ) {
        $sname_k = sanitize_key( $srow['name'] ?? '' );
        $sval_k  = $srow['value'] ?? '';
        if ( ! $sname_k ) continue;
        if ( strpos( $sval_k, 'inset' ) !== false || strpos( $sname_k, 'inner' ) !== false ) {
          $sh_inner[] = [ 'si' => $si, 'srow' => $srow, 'sname' => $sname_k, 'sval' => $sval_k ];
        } else {
          $sh_outer[] = [ 'si' => $si, 'srow' => $srow, 'sname' => $sname_k, 'sval' => $sval_k ];
        }
      }
      $sh_render = function( $entries, $opt ) { ?>
          <?php foreach ( $entries as $idx => $e ) :
            $sname = $e['sname']; $sval = $e['sval']; $si = $e['si']; $srow = $e['srow']; ?>
          <div class="v2-tr" id="v2-tr-sh-<?php echo esc_attr( $sname ); ?>">
            <div class="v2-sh-row<?php echo $idx === 0 ? ' v2-sh-row--active' : ''; ?>"
                 onclick="ecfV2PickShadow('<?php echo esc_js( $sname ); ?>','<?php echo esc_js( $sval ); ?>','<?php echo esc_js( $sval ); ?>')">
              <div class="v2-sh-prev v2-sh-prev--light"><div class="v2-sh-prev-inner" style="box-shadow:<?php echo esc_attr( $sval ); ?>"></div></div>
              <div style="flex:1;min-width:0">
                <div class="v2-sh-name"><?php echo esc_html( $sname ); ?></div>
                <div class="v2-sh-css"><?php echo esc_html( $sval ); ?></div>
              </div>
              <div class="v2-tr-meta" style="margin-left:auto">
                <button type="button" class="v2-chip v2-chip--hi v2-chip--copy" onclick="event.stopPropagation();ecfV2CopyText('--ecf-shadow-<?php echo esc_js( $sname ); ?>')" title="<?php esc_attr_e( 'Variablenname kopieren', 'ecf-framework' ); ?>">--ecf-shadow-<?php echo esc_html( $sname ); ?></button>
                <button type="button" class="v2-edit-btn" onclick="event.stopPropagation();ecfV2ToggleEdit('sh-<?php echo esc_js( $sname ); ?>')" title="<?php esc_attr_e( 'Token bearbeiten', 'ecf-framework' ); ?>" aria-label="<?php esc_attr_e( 'Token bearbeiten', 'ecf-framework' ); ?>">
                  <svg width="11" height="11" viewBox="0 0 13 13" fill="none"><path d="M8.5 2L11 4.5 5 10.5H2.5V8L8.5 2z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
              </div>
            </div>
            <div class="v2-tr-edit" id="v2-edit-sh-<?php echo esc_attr( $sname ); ?>">
              <div class="v2-shadow-edit-panel">
                <label class="v2-sl"><?php echo esc_html( '--ecf-shadow-' . $sname ); ?></label>
                <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[shadows][<?php echo $si; ?>][name]" value="<?php echo esc_attr( $srow['name'] ?? $sname ); ?>">
                <input type="text" class="v2-si v2-shadow-inp"
                       name="<?php echo esc_attr( $opt ); ?>[shadows][<?php echo $si; ?>][value]"
                       id="v2-shval-<?php echo esc_attr( $sname ); ?>"
                       value="<?php echo esc_attr( $sval ); ?>"
                       placeholder="0 4px 16px rgba(0,0,0,.10)"
                       style="width:100%"
                       data-v2-shadow-id="<?php echo esc_attr( $sname ); ?>">
                <div class="v2-shadow-edit-preview" id="v2-shprev-<?php echo esc_attr( $sname ); ?>" style="box-shadow:<?php echo esc_attr( $sval ); ?>"></div>
                <div class="v2-color-edit-actions">
                  <button type="button" class="v2-btn v2-btn--ghost" onclick="ecfV2CopyShadowCSS('<?php echo esc_js( $sname ); ?>')"><?php esc_html_e( 'CSS kopieren', 'ecf-framework' ); ?></button>
                  <button type="button" class="v2-btn v2-btn--ghost" onclick="ecfV2ToggleEdit('sh-<?php echo esc_js( $sname ); ?>')"><?php esc_html_e( 'Close', 'ecf-framework' ); ?></button>
                  <button type="button" class="v2-btn v2-btn--ghost" style="margin-left:auto;color:var(--v2-text3)" data-v2-remove-row="shadow" data-v2-row-index="<?php echo esc_attr( $si ); ?>">✕ <?php esc_html_e( 'Remove', 'ecf-framework' ); ?></button>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
      <?php }; ?>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Außenschatten', 'ecf-framework' ); ?></div>
        <div class="v2-tl" data-v2-tl-shadow>
          <?php $sh_render( $sh_outer, $opt ); ?>
          <div style="padding:8px 4px 0">
            <button type="button" class="v2-btn v2-btn--ghost" data-v2-add-row="shadow" style="width:100%;justify-content:center">+ <?php esc_html_e( 'Add Shadow', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>
      <div class="v2-sec" style="margin-top:16px">
        <div class="v2-sh"><?php esc_html_e( 'Innenschatten', 'ecf-framework' ); ?></div>
        <div class="v2-tl" data-v2-tl-shadow-inner>
          <?php $sh_render( $sh_inner, $opt ); ?>
          <div style="padding:8px 4px 0">
            <button type="button" class="v2-btn v2-btn--ghost" data-v2-add-row="shadow-inner" style="width:100%;justify-content:center">+ <?php esc_html_e( 'Add Shadow', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>
    </div>
    <aside class="v2-aside">
      <div class="v2-as-head"><?php esc_html_e( 'Vorschau', 'ecf-framework' ); ?></div>
      <div class="v2-sh-focus" id="v2-sh-focus">
        <div class="v2-sh-focus-card" id="v2-sh-focus-card" style="box-shadow:<?php echo esc_attr( $sh['xs'] ?? '0 1px 2px rgba(0,0,0,.05)' ); ?>">
          <div class="v2-sh-fname" id="v2-sh-fname">xs</div>
          <div class="v2-sh-ftoken" id="v2-sh-ftoken">--ecf-shadow-xs</div>
          <div class="v2-sh-fcss" id="v2-sh-fcss"><?php echo esc_html( $sh['xs'] ?? '' ); ?></div>
        </div>
      </div>
      <div class="v2-as-head" style="margin-top:16px"><?php esc_html_e( 'Utility-Klassen', 'ecf-framework' ); ?></div>
      <?php foreach ( $sh as $shk => $shv ) : ?>
      <div class="v2-as-row v2-sh-util-row" data-sh-name="<?php echo esc_attr( $shk ); ?>" data-sh-css="<?php echo esc_attr( $shv ); ?>" style="cursor:pointer">
        <span class="v2-as-k">ecf-shadow-<?php echo esc_html( $shk ); ?></span>
        <span class="v2-as-v v2-chip"><?php esc_html_e( 'aktiv', 'ecf-framework' ); ?></span>
      </div>
      <?php endforeach; ?>
    </aside>
  </div>
</div><!-- /shadows page -->

<!-- ═══ PAGE: VARIABLEN ═════════════════════════════════════════════ -->
<div id="ecf-v2-page-variables" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Variablen', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <span class="v2-topbar-note"><?php echo esc_html( $var_count_total . ' ' . __( 'variables total', 'ecf-framework' ) ); ?></span>
      <div class="v2-actions-menu">
        <button type="button" class="v2-btn v2-btn--primary v2-actions-toggle" data-v2-actions-toggle aria-haspopup="menu" aria-expanded="false" title="<?php esc_attr_e( 'Weitere Aktionen', 'ecf-framework' ); ?>"><span class="v2-actions-toggle__label"><?php esc_html_e( 'Aktionen', 'ecf-framework' ); ?></span><span class="v2-actions-toggle__chevron" aria-hidden="true">▾</span></button>
        <div class="v2-actions-menu__dropdown" role="menu" hidden>
          <button type="button" role="menuitem" class="v2-actions-menu__item v2-actions-menu__item--danger" data-v2-reset-defaults title="<?php esc_attr_e( 'Reset ALL settings to plugin defaults', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">↺</span><span><?php esc_html_e( 'Auf Grundeinstellungen zurücksetzen', 'ecf-framework' ); ?></span></button>
          <button type="button" role="menuitem" class="v2-actions-menu__item" data-v2-reset title="<?php esc_attr_e( 'Discard unsaved changes and reload page', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">✕</span><span><?php esc_html_e( 'Änderungen verwerfen', 'ecf-framework' ); ?></span></button>
        </div>
      </div>
      <button type="button" class="v2-btn v2-btn--outline" data-v2-save><?php esc_html_e( 'Speichern', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Variablen', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Live-Übersicht aller Design-Tokens, die beim nächsten Sync in Elementor landen. Werte hier änderst du in den jeweiligen Token-Tabs (Farben, Typografie, Abstände…). Diese Ansicht ist nur zur Kontrolle.', 'ecf-framework' ); ?></p></div>
      <div class="v2-var-search-bar">
        <input type="search" id="v2-var-search" class="v2-si" placeholder="<?php esc_attr_e( 'Variable suchen…', 'ecf-framework' ); ?>" style="max-width:280px">
        <span id="v2-var-search-count" class="v2-var-search-count"></span>
      </div>
      <div class="v2-tabs">
        <button type="button" class="v2-tab v2-tab--on" data-v2-tab-group="vr" data-v2-tab="all"><?php esc_html_e( 'Alle', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $var_count_total ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="vr" data-v2-tab="c"><?php esc_html_e( 'Farben', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $var_count_colors ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="vr" data-v2-tab="t"><?php esc_html_e( 'Typografie', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $typo_steps ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="vr" data-v2-tab="s"><?php esc_html_e( 'Abstände', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $spacing_steps ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="vr" data-v2-tab="r"><?php esc_html_e( 'Radius', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $var_count_radius ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="vr" data-v2-tab="sh"><?php esc_html_e( 'Schatten', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $var_count_shadows ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="vr" data-v2-tab="w"><?php esc_html_e( 'Schriftstärken', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="vr" data-v2-tab="lh"><?php esc_html_e( 'Zeilenhöhen', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="vr" data-v2-tab="ls"><?php esc_html_e( 'Buchstabenabstand', 'ecf-framework' ); ?></button>
      </div>
      <div id="v2-vr-all" class="v2-tp v2-tp--on">
        <table class="v2-vt">
          <thead><tr><th><?php esc_html_e( 'Variable', 'ecf-framework' ); ?></th><th><?php esc_html_e( 'Type', 'ecf-framework' ); ?></th><th><?php esc_html_e( 'Value', 'ecf-framework' ); ?></th><th></th></tr></thead>
          <tbody id="v2-vr-all-tbody">
            <tr class="v2-vt-group"><td colspan="4"><?php esc_html_e( 'Farben', 'ecf-framework' ); ?></td></tr>
            <?php foreach ( $colors_arr as $row ) :
              $cname = sanitize_key( $row['name'] ?? '' );
              $cval  = $row['value'] ?? '';
              if ( ! $cname ) continue;
            ?>
            <tr data-varname="--ecf-color-<?php echo esc_attr( $cname ); ?>"><td><span class="v2-vn">--ecf-color-<?php echo esc_html( $cname ); ?></span></td><td><span class="v2-tb v2-tb-c"><?php esc_html_e( 'Color', 'ecf-framework' ); ?></span></td><td class="v2-vval" style="font-family:var(--v2-mono);font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text2)"><?php echo esc_html( $cval ); ?></td><td><button type="button" class="v2-edit-btn v2-var-edit-btn" data-var-type="color" data-var-name="<?php echo esc_attr( $cname ); ?>" data-var-value="<?php echo esc_attr( $cval ); ?>" title="<?php esc_attr_e( 'Bearbeiten', 'ecf-framework' ); ?>">✎</button></td></tr>
            <?php endforeach; ?>
            <tr class="v2-vt-group"><td colspan="4"><?php esc_html_e( 'Radius', 'ecf-framework' ); ?></td></tr>
            <?php foreach ( $radius_arr as $rrow ) :
              $rname = sanitize_key( $rrow['name'] ?? '' );
              if ( ! $rname ) continue;
              $rmin = $rrow['min'] ?? '';
            ?>
            <tr data-varname="--ecf-radius-<?php echo esc_attr( $rname ); ?>"><td><span class="v2-vn">--ecf-radius-<?php echo esc_html( $rname ); ?></span></td><td><span class="v2-tb v2-tb-r"><?php esc_html_e( 'Radius', 'ecf-framework' ); ?></span></td><td class="v2-vval" style="font-family:var(--v2-mono);font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text2)"><?php echo esc_html( $rmin . ( $rmin !== ( $rrow['max'] ?? '' ) ? '–' . $rrow['max'] : '' ) ); ?></td><td><button type="button" class="v2-edit-btn v2-var-edit-btn" data-var-type="radius" data-var-name="<?php echo esc_attr( $rname ); ?>" data-var-value="<?php echo esc_attr( $rmin ); ?>" data-var-max="<?php echo esc_attr( $rrow['max'] ?? $rmin ); ?>" title="<?php esc_attr_e( 'Bearbeiten', 'ecf-framework' ); ?>">✎</button></td></tr>
            <?php endforeach; ?>
            <tr class="v2-vt-group"><td colspan="4"><?php esc_html_e( 'Schatten', 'ecf-framework' ); ?></td></tr>
            <?php foreach ( $shadows_arr as $srow ) :
              $sname = sanitize_key( $srow['name'] ?? '' );
              $sval  = $srow['value'] ?? '';
              if ( ! $sname ) continue;
            ?>
            <tr data-varname="--ecf-shadow-<?php echo esc_attr( $sname ); ?>"><td><span class="v2-vn">--ecf-shadow-<?php echo esc_html( $sname ); ?></span></td><td><span class="v2-tb v2-tb-sh"><?php esc_html_e( 'Shadow', 'ecf-framework' ); ?></span></td><td class="v2-vval" style="font-family:var(--v2-mono);font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text2);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html( $sval ); ?></td><td><button type="button" class="v2-edit-btn v2-var-edit-btn" data-var-type="shadow" data-var-name="<?php echo esc_attr( $sname ); ?>" data-var-value="<?php echo esc_attr( $sval ); ?>" title="<?php esc_attr_e( 'Bearbeiten', 'ecf-framework' ); ?>">✎</button></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div id="v2-vr-c" class="v2-tp">
        <table class="v2-vt">
          <thead><tr><th><?php esc_html_e( 'Variable', 'ecf-framework' ); ?></th><th><?php esc_html_e( 'Value', 'ecf-framework' ); ?></th></tr></thead>
          <tbody>
            <?php foreach ( $colors_arr as $crow ) :
              $cname = sanitize_key( $crow['name'] ?? '' );
              if ( ! $cname ) continue;
              $chex  = $c[ $cname ] ?? $crow['value'] ?? '';
            ?>
            <tr><td><span class="v2-vn">--ecf-color-<?php echo esc_html( $cname ); ?></span></td><td style="display:flex;align-items:center;gap:6px"><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:<?php echo esc_attr( $chex ); ?>"></span><code style="font-size:var(--v2-btn-fs, 12px)"><?php echo esc_html( $crow['value'] ?? '' ); ?></code></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div id="v2-vr-t"  class="v2-tp"><p class="v2-empty-note"><?php echo esc_html( $typo_steps . ' ' . __( 'Schriftgrößen-Variablen (xs–4xl) — automatisch für alle Bildschirmgrößen angepasst.', 'ecf-framework' ) ); ?></p></div>
      <div id="v2-vr-s"  class="v2-tp"><p class="v2-empty-note"><?php echo esc_html( $spacing_steps . ' ' . __( 'Abstands-Variablen (3xs–4xl) — automatisch für alle Bildschirmgrößen angepasst.', 'ecf-framework' ) ); ?></p></div>
      <div id="v2-vr-r"  class="v2-tp">
        <table class="v2-vt">
          <thead><tr><th><?php esc_html_e( 'Variable', 'ecf-framework' ); ?></th><th><?php esc_html_e( 'Min', 'ecf-framework' ); ?></th><th><?php esc_html_e( 'Max', 'ecf-framework' ); ?></th></tr></thead>
          <tbody>
            <?php foreach ( $radius_arr as $rrow ) :
              $rname = sanitize_key( $rrow['name'] ?? '' );
              if ( ! $rname ) continue;
            ?>
            <tr><td><span class="v2-vn">--ecf-radius-<?php echo esc_html( $rname ); ?></span></td><td style="font-size:var(--v2-ui-base-fs, 13px);font-family:var(--v2-mono)"><?php echo esc_html( $rrow['min'] ?? '' ); ?></td><td style="font-size:var(--v2-ui-base-fs, 13px);font-family:var(--v2-mono)"><?php echo esc_html( $rrow['max'] ?? '' ); ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div id="v2-vr-sh" class="v2-tp">
        <table class="v2-vt">
          <thead><tr><th><?php esc_html_e( 'Variable', 'ecf-framework' ); ?></th><th><?php esc_html_e( 'Value', 'ecf-framework' ); ?></th></tr></thead>
          <tbody>
            <?php foreach ( $shadows_arr as $srow ) :
              $sname = sanitize_key( $srow['name'] ?? '' );
              if ( ! $sname ) continue;
            ?>
            <tr><td><span class="v2-vn">--ecf-shadow-<?php echo esc_html( $sname ); ?></span></td><td style="font-size:var(--v2-btn-fs, 12px);font-family:var(--v2-mono);color:var(--v2-text2);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html( $srow['value'] ?? '' ); ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Schriftstärken (read-only) -->
      <div id="v2-vr-w" class="v2-tp">
        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Schriftstärken', 'ecf-framework' ); ?></div>
          <div style="display:flex;flex-wrap:wrap;gap:8px">
            <?php $weights_arr = $settings['typography']['weights'] ?? [];
            foreach ( $weights_arr as $wrow ) :
              $wname = sanitize_key( $wrow['name'] ?? '' );
              $wval  = $wrow['value'] ?? '400';
              if ( ! $wname ) continue;
            ?>
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;background:var(--v2-surface2,rgba(255,255,255,.04));border:1px solid var(--v2-border);border-radius:8px;padding:10px 14px;min-width:80px">
              <span style="font-size:22px;font-weight:<?php echo esc_attr( $wval ); ?>;color:var(--v2-text);line-height:1">Ag</span>
              <span style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text2);margin-top:2px"><?php echo esc_html( $wname ); ?></span>
              <span style="font-size:var(--v2-ui-base-fs, 13px);font-family:var(--v2-mono);color:var(--v2-text3);margin-top:2px"><?php echo esc_html( $wval ); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Zeilenhöhen (bearbeitbar) -->
      <div id="v2-vr-lh" class="v2-tp">
        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Zeilenhöhen', 'ecf-framework' ); ?> <span title="<?php esc_attr_e( 'Leading = Zeilenhöhe (line-height). Steuert den Abstand zwischen Textzeilen. Unitloser Wert empfohlen (z. B. 1.5 = 1,5-fache Schriftgröße). Enge Werte (1.1–1.3) für Überschriften, weite (1.5–1.7) für Fließtext.', 'ecf-framework' ); ?>" style="font-size:var(--v2-btn-fs, 12px);font-weight:400;color:var(--v2-text3);border:1px solid var(--v2-border);border-radius:50%;padding:0 4px;cursor:help;vertical-align:middle">?</span></div>
          <p style="font-size:12px;color:var(--v2-text2);margin:0 0 12px"><?php esc_html_e( 'Wiederverwendbare Zeilenhöhen als --ecf-leading-* Tokens für Überschriften, Fließtext und UI-Elemente.', 'ecf-framework' ); ?></p>
          <div class="v2-tl v2-tl--ty" id="v2-lh-list">
            <?php
            $lh_defaults = [
              ['name'=>'tight',   'value'=>'1.15'],
              ['name'=>'snug',    'value'=>'1.3'],
              ['name'=>'normal',  'value'=>'1.5'],
              ['name'=>'relaxed', 'value'=>'1.65'],
              ['name'=>'loose',   'value'=>'1.8'],
            ];
            $lh_arr = $settings['typography']['leading'] ?? $lh_defaults;
            foreach ( $lh_arr as $lhi => $lhrow ) :
              $lhname = sanitize_key( $lhrow['name'] ?? '' );
              $lhval  = $lhrow['value'] ?? '1.5';
              if ( ! $lhname ) continue;
            ?>
            <div class="v2-tr v2-tr--compact" data-v2-row-type="lineheight">
              <div class="v2-tr-main">
                <div style="width:28px;flex-shrink:0;display:flex;flex-direction:column;justify-content:center;gap:<?php echo esc_attr( round( (float)$lhval * 4 ) ); ?>px">
                  <div style="background:var(--v2-primary,#6366f1);height:2px;border-radius:1px;opacity:.7"></div>
                  <div style="background:var(--v2-primary,#6366f1);height:2px;border-radius:1px;opacity:.5;width:70%"></div>
                </div>
                <div style="flex:1;min-width:0">
                  <span class="v2-tr-name"><?php echo esc_html( $lhname ); ?></span>
                  <span class="v2-tr-var" style="margin-left:6px">--ecf-leading-<?php echo esc_html( $lhname ); ?></span>
                </div>
                <div class="v2-tr-meta">
                  <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[typography][leading][<?php echo $lhi; ?>][name]" value="<?php echo esc_attr( $lhrow['name'] ?? $lhname ); ?>">
                  <input type="text" class="v2-si v2-si--sm" name="<?php echo esc_attr( $opt ); ?>[typography][leading][<?php echo $lhi; ?>][value]" value="<?php echo esc_attr( $lhval ); ?>" placeholder="1.5" style="width:62px;text-align:center" data-v2-sync-lh="<?php echo esc_attr( $lhi ); ?>">
                  <button type="button" class="v2-edit-btn" data-v2-remove-token-row style="color:var(--v2-text3)">✕</button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="padding:8px 4px 0">
            <button type="button" class="v2-btn v2-btn--ghost" id="v2-lh-add-btn" style="width:100%;justify-content:center">+ <?php esc_html_e( 'Zeilenhöhe hinzufügen', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>

      <!-- Buchstabenabstand (bearbeitbar) -->
      <div id="v2-vr-ls" class="v2-tp">
        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Buchstabenabstand', 'ecf-framework' ); ?> <span title="<?php esc_attr_e( 'Tracking = Buchstabenabstand (letter-spacing). Negative Werte rücken Buchstaben enger zusammen, positive weiter auseinander. Einheit em empfohlen (relativ zur Schriftgröße).', 'ecf-framework' ); ?>" style="font-size:var(--v2-btn-fs, 12px);font-weight:400;color:var(--v2-text3);border:1px solid var(--v2-border);border-radius:50%;padding:0 4px;cursor:help;vertical-align:middle">?</span></div>
          <p style="font-size:12px;color:var(--v2-text2);margin:0 0 12px"><?php esc_html_e( 'Wiederverwendbare Buchstabenabstand-Werte als --ecf-tracking-* Tokens (em-Einheiten empfohlen, z. B. 0.02em).', 'ecf-framework' ); ?></p>
          <div class="v2-tl v2-tl--ty" id="v2-ls-list">
            <?php
            $ls_defaults = [
              ['name'=>'tight',   'value'=>'-0.02em'],
              ['name'=>'normal',  'value'=>'0em'],
              ['name'=>'wide',    'value'=>'0.04em'],
              ['name'=>'wider',   'value'=>'0.08em'],
              ['name'=>'widest',  'value'=>'0.16em'],
            ];
            $ls_arr = $settings['typography']['tracking'] ?? $ls_defaults;
            foreach ( $ls_arr as $lsi => $lsrow ) :
              $lsname = sanitize_key( $lsrow['name'] ?? '' );
              $lsval  = $lsrow['value'] ?? '0em';
              if ( ! $lsname ) continue;
            ?>
            <div class="v2-tr v2-tr--compact" data-v2-row-type="letterspacing">
              <div class="v2-tr-main">
                <span style="font-size:13px;font-weight:600;letter-spacing:<?php echo esc_attr( $lsval ); ?>;color:var(--v2-text2);flex-shrink:0;width:28px;text-align:center">Aa</span>
                <div style="flex:1;min-width:0">
                  <span class="v2-tr-name"><?php echo esc_html( $lsname ); ?></span>
                  <span class="v2-tr-var" style="margin-left:6px">--ecf-tracking-<?php echo esc_html( $lsname ); ?></span>
                </div>
                <div class="v2-tr-meta">
                  <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[typography][tracking][<?php echo $lsi; ?>][name]" value="<?php echo esc_attr( $lsrow['name'] ?? $lsname ); ?>">
                  <input type="text" class="v2-si v2-si--sm" name="<?php echo esc_attr( $opt ); ?>[typography][tracking][<?php echo $lsi; ?>][value]" value="<?php echo esc_attr( $lsval ); ?>" placeholder="0.04em" style="width:72px;text-align:center">
                  <button type="button" class="v2-edit-btn" data-v2-remove-token-row style="color:var(--v2-text3)">✕</button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="padding:8px 4px 0">
            <button type="button" class="v2-btn v2-btn--ghost" id="v2-ls-add-btn" style="width:100%;justify-content:center">+ <?php esc_html_e( 'Buchstabenabstand hinzufügen', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>
    </div>
    <aside class="v2-aside">
      <div class="v2-as-head"><?php esc_html_e( 'Overview', 'ecf-framework' ); ?></div>
      <div class="v2-as-row"><span class="v2-as-k"><?php esc_html_e( 'Colors', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $var_count_colors ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k"><?php esc_html_e( 'Text', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $typo_steps ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k"><?php esc_html_e( 'Spacing', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $spacing_steps ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k"><?php esc_html_e( 'Radius', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $var_count_radius ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k"><?php esc_html_e( 'Shadows', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $var_count_shadows ); ?></span></div>
      <div class="v2-as-row v2-as-row--total"><span class="v2-as-k"><?php esc_html_e( 'Total', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $var_count_total ); ?></span></div>
      <div class="v2-as-block">
        <?php
          $v_total = (int) ( $limit_snap['variables_total'] ?? $var_count_total );
          $v_limit = (int) ( $limit_snap['variables_limit'] ?? 1000 );
          $v_pct   = min( 100, (int) round( $v_total / max( 1, $v_limit ) * 100 ) );
        ?>
        <div class="v2-limit-overview">
          <div class="v2-limit-eyebrow"><?php esc_html_e( 'Variablen-Übersicht', 'ecf-framework' ); ?></div>
          <div class="v2-limit-hero">
            <span class="v2-limit-big"><?php echo esc_html( $v_total ); ?></span>
            <span class="v2-limit-of"><?php esc_html_e( 'von', 'ecf-framework' ); ?></span>
            <span class="v2-limit-big"><?php echo esc_html( $v_limit ); ?></span>
          </div>
          <div class="v2-limit-sub"><?php esc_html_e( 'Variablen in Elementor', 'ecf-framework' ); ?></div>
          <div class="v2-limit-bar"><div class="v2-limit-fill" style="width:<?php echo esc_attr( $v_pct ); ?>%"></div></div>
          <div class="v2-limit-details">
            <div class="v2-limit-detail-item"><span><?php esc_html_e( 'Aus Layrix', 'ecf-framework' ); ?></span><strong><?php echo esc_html( $var_count_total ); ?></strong></div>
            <div class="v2-limit-detail-item"><span><?php esc_html_e( 'Verfügbar', 'ecf-framework' ); ?></span><strong><?php echo esc_html( max( 0, $v_limit - $v_total ) ); ?></strong></div>
          </div>
        </div>
      </div>
    </aside>
  </div>
</div><!-- /variables page -->

<!-- ═══ PAGE: VORSCHAU ═══════════════════════════════════════════════ -->
<div id="ecf-v2-page-preview" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Vorschau', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <div class="v2-tog-label" style="gap:6px">
        <span style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3)"><?php esc_html_e( 'Dark Mode', 'ecf-framework' ); ?></span>
        <span class="v2-tog v2-tog--off" id="v2-pv-dark-tog"></span>
        <input type="checkbox" class="v2-tog-cb" id="v2-pv-dark-cb" style="display:none">
      </div>
      <button type="button" class="v2-btn v2-btn--ghost" id="v2-pv-refresh" title="<?php esc_attr_e( 'Vorschau aktualisieren', 'ecf-framework' ); ?>"><?php esc_html_e( 'Aktualisieren', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body v2-page-body--full">
    <style id="v2-pv-vars">/* populated by JS */</style>
    <div id="v2-pv-canvas" class="v2-pv-canvas">

      <!-- Mock hero -->
      <section class="v2-pv-hero">
        <div class="v2-pv-hero-label"><?php esc_html_e( 'Typografie', 'ecf-framework' ); ?></div>
        <h1 class="v2-pv-h1"><?php esc_html_e( 'Design das überzeugt', 'ecf-framework' ); ?></h1>
        <p class="v2-pv-body"><?php esc_html_e( 'Dein Design-System mit konsistenten Tokens für Farben, Abstände und Typografie — direkt in Elementor.', 'ecf-framework' ); ?></p>
        <div class="v2-pv-actions">
          <button class="v2-pv-btn v2-pv-btn--primary"><?php esc_html_e( 'Primär-Button', 'ecf-framework' ); ?></button>
          <button class="v2-pv-btn v2-pv-btn--secondary"><?php esc_html_e( 'Sekundär', 'ecf-framework' ); ?></button>
        </div>
      </section>

      <!-- Color palette -->
      <section class="v2-pv-sec">
        <div class="v2-pv-sec-title"><?php esc_html_e( 'Farb-Palette', 'ecf-framework' ); ?></div>
        <div class="v2-pv-colors" id="v2-pv-colors">
          <?php foreach ( $colors_arr as $row ) :
            $cn = sanitize_key( $row['name'] ?? '' );
            $cv = $c[ $cn ] ?? '#000';
            if ( ! $cn ) continue;
          ?>
          <div class="v2-pv-swatch" style="background:var(--pv-color-<?php echo esc_attr( $cn ); ?>,<?php echo esc_attr( $cv ); ?>)" title="--ecf-color-<?php echo esc_attr( $cn ); ?>">
            <span class="v2-pv-swatch-name"><?php echo esc_html( $cn ); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Typography scale -->
      <section class="v2-pv-sec">
        <div class="v2-pv-sec-title"><?php esc_html_e( 'Typografie-Skala', 'ecf-framework' ); ?></div>
        <div class="v2-pv-scale" id="v2-pv-scale">
          <!-- Filled by JS -->
        </div>
      </section>

      <!-- Cards row -->
      <section class="v2-pv-sec">
        <div class="v2-pv-sec-title"><?php esc_html_e( 'Karten & Abstände', 'ecf-framework' ); ?></div>
        <div class="v2-pv-cards">
          <?php foreach ( [
            [ __( 'Primär', 'ecf-framework' ), 'primary',   __( 'Dein Brand-Hauptton für CTAs, Links und Highlights.', 'ecf-framework' ) ],
            [ __( 'Sekundär', 'ecf-framework' ), 'secondary', __( 'Unterstützender Ton für weniger prominente Elemente.', 'ecf-framework' ) ],
            [ __( 'Akzent', 'ecf-framework' ), 'accent',    __( 'Kleiner Farbklecks für Badges, Pfeile und Dekoelemente.', 'ecf-framework' ) ],
          ] as [$title, $col, $desc] ) : ?>
          <div class="v2-pv-card">
            <div class="v2-pv-card-accent" style="background:var(--pv-color-<?php echo esc_attr( $col ); ?>,var(--v2-primary))"></div>
            <div class="v2-pv-card-body">
              <div class="v2-pv-card-title"><?php echo esc_html( $title ); ?></div>
              <div class="v2-pv-card-desc"><?php echo esc_html( $desc ); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Radius samples -->
      <section class="v2-pv-sec">
        <div class="v2-pv-sec-title"><?php esc_html_e( 'Rahmenradien', 'ecf-framework' ); ?></div>
        <div class="v2-pv-radii">
          <?php foreach ( $radius_arr as $row ) :
            $rn  = sanitize_key( $row['name'] ?? '' );
            $rmin = $row['min'] ?? '4px';
            if ( ! $rn ) continue;
          ?>
          <div class="v2-pv-radius-box" style="border-radius:var(--pv-radius-<?php echo esc_attr($rn); ?>,<?php echo esc_attr($rmin); ?>)">
            <span><?php echo esc_html( $rn ); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

    </div><!-- /canvas -->
  </div>
</div><!-- /preview page -->

<!-- Variable edit modal -->
<div id="v2-var-modal" class="v2-modal-overlay" hidden>
  <div class="v2-modal-box">
    <div class="v2-modal-head">
      <span id="v2-var-modal-title"><?php esc_html_e( 'Variable bearbeiten', 'ecf-framework' ); ?></span>
      <button type="button" class="v2-modal-close" data-v2-var-modal-close>✕</button>
    </div>
    <div class="v2-modal-body">
      <div class="v2-sl" id="v2-var-modal-var-name" style="font-family:var(--v2-mono);color:var(--v2-accent2);margin-bottom:10px"></div>
      <input type="text" class="v2-si" id="v2-var-modal-input" style="width:100%" placeholder="value">
      <div id="v2-var-modal-radius-row" style="display:none;margin-top:8px">
        <div style="display:flex;gap:8px;align-items:center">
          <label class="v2-mini-label" style="min-width:28px"><?php esc_html_e( 'Min', 'ecf-framework' ); ?></label>
          <input type="text" class="v2-si v2-si--sm" id="v2-var-modal-input-min" style="flex:1" placeholder="4px">
          <label class="v2-mini-label" style="min-width:28px"><?php esc_html_e( 'Max', 'ecf-framework' ); ?></label>
          <input type="text" class="v2-si v2-si--sm" id="v2-var-modal-input-max" style="flex:1" placeholder="8px">
        </div>
      </div>
      <div id="v2-var-modal-color-preview" style="display:none;margin-top:8px;height:32px;border-radius:6px;border:1px solid var(--v2-border)"></div>
    </div>
    <div class="v2-modal-foot">
      <button type="button" class="v2-btn v2-btn--ghost" data-v2-var-modal-close><?php esc_html_e( 'Abbrechen', 'ecf-framework' ); ?></button>
      <button type="button" class="v2-btn v2-btn--primary" id="v2-var-modal-save"><?php esc_html_e( 'Übernehmen', 'ecf-framework' ); ?></button>
    </div>
  </div>
</div>

<!-- ═══ PAGE: KLASSEN ═══════════════════════════════════════════════ -->
<div id="ecf-v2-page-classes" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Classes', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <button type="button" class="v2-btn v2-btn--outline" data-v2-save><?php esc_html_e( 'Speichern', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Klassen', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Starter- und Utility-Klassen für semantische Struktur — aktivieren was gebraucht wird, dann mit Elementor synchronisieren.', 'ecf-framework' ); ?></p></div>
      <div class="v2-tabs">
        <button type="button" class="v2-tab v2-tab--on" data-v2-tab-group="cl" data-v2-tab="active" title="<?php esc_attr_e( 'Übersicht aller aktivierten Klassen', 'ecf-framework' ); ?>"><?php esc_html_e( 'Aktive', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $active_total ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="cl" data-v2-tab="starter"><?php esc_html_e( 'Starter', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $active_starter_basic . '/' . count( $starter_lib['basic'] ?? [] ) ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="cl" data-v2-tab="extra" title="<?php esc_attr_e( 'Erweiterte Layout- und Komponenten-Klassen', 'ecf-framework' ); ?>"><?php esc_html_e( 'Extra', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $active_starter_extra . '/' . count( $starter_lib['advanced'] ?? [] ) ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="cl" data-v2-tab="utility"><?php esc_html_e( 'Utility', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $active_utility ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="cl" data-v2-tab="custom"><?php esc_html_e( 'Eigene', 'ecf-framework' ); ?><span class="v2-tc"><?php echo esc_html( $active_custom ); ?></span></button>
        <button type="button" class="v2-tab" data-v2-tab-group="cl" data-v2-tab="generator" title="<?php esc_attr_e( 'BEM-Klassen automatisch generieren und zu eigenen Klassen hinzufügen', 'ecf-framework' ); ?>"><?php esc_html_e( 'BEM-Generator', 'ecf-framework' ); ?></button>
      </div>

      <!-- Active classes (read-only overview) -->
      <div id="v2-cl-active" class="v2-tp v2-tp--on">
        <?php
          $active_groups = [
            'starter' => [ 'label' => __( 'Starter', 'ecf-framework' ), 'chip' => 'v2-chip v2-chip--green', 'rows' => [] ],
            'extra'   => [ 'label' => __( 'Extra',   'ecf-framework' ), 'chip' => 'v2-chip',                'rows' => [] ],
            'utility' => [ 'label' => __( 'Utility', 'ecf-framework' ), 'chip' => 'v2-chip',                'rows' => [] ],
            'custom'  => [ 'label' => __( 'Eigene',  'ecf-framework' ), 'chip' => 'v2-chip',                'rows' => [] ],
          ];
          foreach ( $starter_lib['basic'] ?? [] as $cls ) {
            if ( ! empty( $enabled_map[ $cls['name'] ] ) ) {
              $active_groups['starter']['rows'][] = [ 'name' => $cls['name'], 'desc' => ucfirst( $cls['category'] ?? '' ) ];
            }
          }
          foreach ( $starter_lib['advanced'] ?? [] as $cls ) {
            if ( ! empty( $enabled_map[ $cls['name'] ] ) ) {
              $active_groups['extra']['rows'][] = [ 'name' => $cls['name'], 'desc' => ucfirst( $cls['category'] ?? '' ) ];
            }
          }
          foreach ( $utility_lib as $group_items ) {
            foreach ( $group_items as $cls ) {
              if ( ! empty( $utility_enabled[ $cls['name'] ] ) ) {
                $active_groups['utility']['rows'][] = [ 'name' => $cls['name'], 'desc' => $cls['label'] ?? '' ];
              }
            }
          }
          foreach ( $custom_classes as $row ) {
            $cn = trim( (string) ( $row['name'] ?? '' ) );
            if ( $cn !== '' ) $active_groups['custom']['rows'][] = [ 'name' => $cn, 'desc' => __( 'Custom', 'ecf-framework' ) ];
          }
        ?>
        <?php if ( $active_total === 0 ) : ?>
          <div class="v2-empty-state">
            <div class="v2-empty-state-icon">∅</div>
            <div class="v2-empty-state-title"><?php esc_html_e( 'Noch keine Klassen aktiv', 'ecf-framework' ); ?></div>
            <div class="v2-empty-state-desc"><?php esc_html_e( 'Aktiviere Klassen in den Tabs Starter, Extra, Utility oder Eigene.', 'ecf-framework' ); ?></div>
          </div>
        <?php else : foreach ( $active_groups as $tab_id => $grp ) : if ( empty( $grp['rows'] ) ) continue; ?>
          <div class="v2-cl-group-label" style="display:flex;align-items:center;justify-content:space-between;gap:8px">
            <span><?php echo esc_html( $grp['label'] ); ?> <span style="opacity:.6">(<?php echo (int) count( $grp['rows'] ); ?>)</span></span>
            <button type="button" class="v2-edit-btn" data-v2-jump-tab="<?php echo esc_attr( $tab_id ); ?>" title="<?php esc_attr_e( 'Verwalten', 'ecf-framework' ); ?>"><?php esc_html_e( 'Verwalten →', 'ecf-framework' ); ?></button>
          </div>
          <?php foreach ( $grp['rows'] as $r ) : ?>
          <div class="v2-cl-row">
            <div><div class="v2-cl-name">.<?php echo esc_html( sanitize_html_class( $r['name'] ) ); ?></div><div class="v2-cl-desc"><?php echo esc_html( $r['desc'] ); ?></div></div>
            <span class="<?php echo esc_attr( $grp['chip'] ); ?>"><?php echo esc_html( $grp['label'] ); ?></span>
          </div>
          <?php endforeach; ?>
        <?php endforeach; endif; ?>
      </div><!-- /active tab -->

      <?php
        /* Render helper for grouped class rows with bulk toggle + searchable
           data-attrs. Used by Starter, Extra and Utility tabs.
           $rows_by_cat: ['categoryKey' => [['name'=>..., 'desc'=>..., 'enabled'=>bool], ...]]
           $name_tpl:    sprintf-style template, %s gets replaced by cls name
           $chip_class:  chip CSS class (e.g. 'v2-chip v2-chip--green')
           $chip_label:  chip text */
        $render_class_group = function ( $rows_by_cat, $name_tpl, $chip_class, $chip_label ) use ( $opt ) {
          ksort( $rows_by_cat );
          foreach ( $rows_by_cat as $cat => $rows ) :
            if ( empty( $rows ) ) continue;
            $cat_attr = sanitize_key( $cat );
        ?>
        <div class="v2-cl-group-label v2-cl-group-head" data-v2-cl-cat="<?php echo esc_attr( $cat_attr ); ?>" style="display:flex;align-items:center;justify-content:space-between;gap:8px">
          <span><?php echo esc_html( ucfirst( $cat ) ); ?> <span style="opacity:.6">(<?php echo (int) count( $rows ); ?>)</span></span>
          <span class="v2-cl-bulk" style="display:flex;gap:6px">
            <button type="button" class="v2-edit-btn" data-v2-cl-bulk="on"  data-v2-cl-cat="<?php echo esc_attr( $cat_attr ); ?>" title="<?php esc_attr_e( 'Alle aktivieren', 'ecf-framework' ); ?>"><?php esc_html_e( 'Alle ein', 'ecf-framework' ); ?></button>
            <button type="button" class="v2-edit-btn" data-v2-cl-bulk="off" data-v2-cl-cat="<?php echo esc_attr( $cat_attr ); ?>" title="<?php esc_attr_e( 'Alle deaktivieren', 'ecf-framework' ); ?>"><?php esc_html_e( 'Alle aus', 'ecf-framework' ); ?></button>
          </span>
        </div>
        <?php foreach ( $rows as $r ) :
            $cls_name = sanitize_html_class( $r['name'] );
            $field    = sprintf( $name_tpl, esc_attr( $r['name'] ) );
        ?>
        <div class="v2-cl-row" data-v2-cl-cat="<?php echo esc_attr( $cat_attr ); ?>" data-v2-cl-name="<?php echo esc_attr( strtolower( $r['name'] ) ); ?>" data-v2-cl-desc="<?php echo esc_attr( strtolower( $r['desc'] ) ); ?>">
          <div><div class="v2-cl-name">.<?php echo esc_html( $cls_name ); ?></div><div class="v2-cl-desc"><?php echo esc_html( $r['desc'] ); ?></div></div>
          <span class="<?php echo esc_attr( $chip_class ); ?>"><?php echo esc_html( $chip_label ); ?></span>
          <label class="v2-tog-label">
            <input type="checkbox"
                   class="v2-tog-cb"
                   name="<?php echo esc_attr( $opt . $field ); ?>"
                   value="1"
                   <?php checked( $r['enabled'] ); ?>>
            <span class="v2-tog<?php echo $r['enabled'] ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
          </label>
        </div>
        <?php endforeach; ?>
        <?php endforeach;
        };

        /* Search box markup, parameter is the wrapper id of the tab panel. */
        $render_class_search = function ( $panel_id ) {
        ?>
        <div class="v2-cl-search-wrap" style="margin-bottom:12px;position:sticky;top:0;z-index:5;background:var(--v2-bg);padding:6px 0">
          <input type="search" class="v2-si v2-cl-search" data-v2-cl-search="<?php echo esc_attr( $panel_id ); ?>" placeholder="<?php esc_attr_e( 'Klassen suchen…', 'ecf-framework' ); ?>" style="width:100%">
          <div class="v2-cl-search-empty" data-v2-cl-empty="<?php echo esc_attr( $panel_id ); ?>" style="display:none;padding:24px;text-align:center;opacity:.7"><?php esc_html_e( 'Keine Klassen gefunden', 'ecf-framework' ); ?></div>
        </div>
        <?php
        };
      ?>

      <!-- Starter classes (basic only, grouped by category) -->
      <div id="v2-cl-starter" class="v2-tp">
        <?php $render_class_search( 'v2-cl-starter' ); ?>
        <?php
          $starter_by_cat = [];
          foreach ( $starter_lib['basic'] ?? [] as $cls ) {
            $cat = $cls['category'] ?? 'other';
            $starter_by_cat[ $cat ][] = [
              'name'    => $cls['name'],
              'desc'    => ucfirst( $cls['category'] ?? '' ),
              'enabled' => ! empty( $enabled_map[ $cls['name'] ] ),
            ];
          }
          $render_class_group( $starter_by_cat, '[starter_classes][enabled][%s]', 'v2-chip v2-chip--green', __( 'Starter', 'ecf-framework' ) );
        ?>
      </div><!-- /starter tab -->

      <!-- Extra classes (advanced, grouped by category) -->
      <div id="v2-cl-extra" class="v2-tp">
        <?php $render_class_search( 'v2-cl-extra' ); ?>
        <?php
          $extra_by_cat = [];
          foreach ( $starter_lib['advanced'] ?? [] as $cls ) {
            $cat = $cls['category'] ?? 'other';
            $extra_by_cat[ $cat ][] = [
              'name'    => $cls['name'],
              'desc'    => ucfirst( $cls['category'] ?? '' ),
              'enabled' => ! empty( $enabled_map[ $cls['name'] ] ),
            ];
          }
          $render_class_group( $extra_by_cat, '[starter_classes][enabled][%s]', 'v2-chip', __( 'Extra', 'ecf-framework' ) );
        ?>
      </div><!-- /extra tab -->

      <!-- Utility classes (already grouped by registry, just add search/bulk) -->
      <div id="v2-cl-utility" class="v2-tp">
        <?php $render_class_search( 'v2-cl-utility' ); ?>
        <?php
          $utility_by_cat = [];
          foreach ( $utility_lib as $group_key => $group_items ) {
            foreach ( $group_items as $cls ) {
              $utility_by_cat[ $group_key ][] = [
                'name'    => $cls['name'],
                'desc'    => $cls['label'] ?? '',
                'enabled' => ! empty( $utility_enabled[ $cls['name'] ] ),
              ];
            }
          }
          $render_class_group( $utility_by_cat, '[utility_classes][enabled][%s]', 'v2-chip', __( 'Utility', 'ecf-framework' ) );
        ?>
      </div><!-- /utility tab -->

      <!-- Custom classes -->
      <div id="v2-cl-custom" class="v2-tp">
        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Custom Classes', 'ecf-framework' ); ?></div>
          <div id="v2-custom-class-list">
          <div id="v2-cc-empty" class="v2-empty-state"<?php echo $active_custom > 0 ? ' style="display:none"' : ''; ?>>
            <div class="v2-empty-state-icon">{ }</div>
            <div class="v2-empty-state-title"><?php esc_html_e( 'Noch keine eigenen Klassen', 'ecf-framework' ); ?></div>
            <div class="v2-empty-state-desc"><?php esc_html_e( 'Eigene CSS-Klassen werden beim Sync als leere Selector-Hüllen in Elementor eingetragen — du kannst sie dann im Theme per CSS befüllen.', 'ecf-framework' ); ?></div>
            <div class="v2-empty-state-example"><code>.is-hero-section</code> &nbsp;·&nbsp; <code>.card--featured</code> &nbsp;·&nbsp; <code>.u-text-balance</code></div>
          </div>
          <?php foreach ( $custom_classes as $ci => $crow ) :
            $cname = trim( (string) ( $crow['name'] ?? '' ) );
            if ( ! $cname ) continue;
          ?>
          <div class="v2-cl-row" id="v2-cc-<?php echo esc_attr( $ci ); ?>">
            <input type="text" class="v2-si" style="flex:1"
                   name="<?php echo esc_attr( $opt ); ?>[starter_classes][custom][<?php echo $ci; ?>][name]"
                   value="<?php echo esc_attr( $cname ); ?>"
                   placeholder="my-custom-class">
            <span class="v2-chip">Custom</span>
            <button type="button" class="v2-edit-btn" data-v2-remove-custom-class title="<?php esc_attr_e( 'Remove', 'ecf-framework' ); ?>">✕</button>
          </div>
          <?php endforeach; ?>
          </div>
          <div style="margin-top:8px">
            <button type="button" class="v2-btn v2-btn--ghost" id="v2-add-custom-class" style="width:100%;justify-content:center">+ <?php esc_html_e( 'Eigene Klasse hinzufügen', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div><!-- /custom tab -->

      <!-- BEM Generator tab -->
      <div id="v2-cl-generator" class="v2-tp">
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'BEM Generator', 'ecf-framework' ); ?></div>
        <div class="v2-bem-explain">
          <div class="v2-bem-explain-row"><code>Block</code> <span><?php esc_html_e( 'Eigenständige Komponente', 'ecf-framework' ); ?></span> <code class="v2-bem-eg">.card</code></div>
          <div class="v2-bem-explain-row"><code>Block__Element</code> <span><?php esc_html_e( 'Teil des Blocks', 'ecf-framework' ); ?></span> <code class="v2-bem-eg">.card__title</code></div>
          <div class="v2-bem-explain-row"><code>Block--Modifier</code> <span><?php esc_html_e( 'Variante des Blocks', 'ecf-framework' ); ?></span> <code class="v2-bem-eg">.card--dark</code></div>
        </div>
        <div class="v2-sr" style="margin-bottom:10px">
          <div><div class="v2-sl"><?php esc_html_e( 'Preset', 'ecf-framework' ); ?></div><div class="v2-sh2"><?php esc_html_e( 'Schnellstart-Vorlage', 'ecf-framework' ); ?></div></div>
          <select class="v2-si" id="v2-bem-preset" style="max-width:200px" onchange="ecfV2ApplyBEMPreset(this.value)">
            <option value=""><?php esc_html_e( '— Preset wählen —', 'ecf-framework' ); ?></option>
            <option value="hero|headline|dark"><?php esc_html_e( 'Hero Section', 'ecf-framework' ); ?></option>
            <option value="card|body|featured"><?php esc_html_e( 'Card', 'ecf-framework' ); ?></option>
            <option value="header|nav|sticky"><?php esc_html_e( 'Header / Navigation', 'ecf-framework' ); ?></option>
            <option value="section|inner|alt"><?php esc_html_e( 'Section', 'ecf-framework' ); ?></option>
            <option value="btn|label|primary"><?php esc_html_e( 'Button', 'ecf-framework' ); ?></option>
            <option value="form|field|error"><?php esc_html_e( 'Form Field', 'ecf-framework' ); ?></option>
            <option value="modal|content|open"><?php esc_html_e( 'Modal', 'ecf-framework' ); ?></option>
            <option value="badge||pill"><?php esc_html_e( 'Badge / Pill', 'ecf-framework' ); ?></option>
            <option value="testimonial|quote|highlighted"><?php esc_html_e( 'Testimonial', 'ecf-framework' ); ?></option>
            <option value="pricing|tier|popular"><?php esc_html_e( 'Pricing Card', 'ecf-framework' ); ?></option>
          </select>
        </div>
        <p style="font-size:11.5px;color:var(--v2-text3);margin:0 0 12px;line-height:1.5"><?php esc_html_e( 'Fülle Block aus — Ergebnis erscheint automatisch unten. Mit "Zu eigenen Klassen hinzufügen" wird die Klasse direkt angelegt.', 'ecf-framework' ); ?></p>
        <div class="v2-sg">
          <div class="v2-sr">
            <div><div class="v2-sl">Block</div></div>
            <input type="text" class="v2-si" id="v2-bem-block" placeholder="card" oninput="ecfV2UpdateBEM()" style="max-width:160px">
          </div>
          <div class="v2-sr">
            <div><div class="v2-sl">Element</div><div class="v2-sh2"><?php esc_html_e( 'optional', 'ecf-framework' ); ?></div></div>
            <input type="text" class="v2-si" id="v2-bem-elem" placeholder="title" oninput="ecfV2UpdateBEM()" style="max-width:160px">
          </div>
          <div class="v2-sr">
            <div><div class="v2-sl">Modifier</div><div class="v2-sh2"><?php esc_html_e( 'optional', 'ecf-framework' ); ?></div></div>
            <input type="text" class="v2-si" id="v2-bem-mod" placeholder="dark" oninput="ecfV2UpdateBEM()" style="max-width:160px">
          </div>
        </div>
        <div style="margin-top:10px">
          <div class="v2-sl" style="margin-bottom:6px"><?php esc_html_e( 'Schnell-Modifier', 'ecf-framework' ); ?></div>
          <div class="v2-bem-chips">
            <?php foreach ( [ 'dark','light','sm','lg','active','disabled','featured','alt','primary','secondary','ghost','outline','rounded','full' ] as $chip ) : ?>
            <label class="v2-bem-chip"><input type="checkbox" class="v2-bem-chip-cb" data-bem-mod="<?php echo esc_attr( $chip ); ?>"><span><?php echo esc_html( $chip ); ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>
        <div id="v2-bem-output" style="margin-top:10px;padding:12px;background:rgba(255,255,255,.03);border:1px solid var(--v2-border);border-radius:7px">
          <div style="font-size:var(--v2-btn-fs, 12px);font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--v2-text3);margin-bottom:6px"><?php esc_html_e( 'Ergebnis', 'ecf-framework' ); ?></div>
          <div id="v2-bem-result" style="font-family:var(--v2-mono);font-size:12px;color:var(--v2-accent2);min-height:18px"><?php esc_html_e( '← Block ausfüllen', 'ecf-framework' ); ?></div>
          <div style="display:flex;gap:6px;margin-top:8px">
            <button type="button" class="v2-btn v2-btn--primary" onclick="ecfV2AddBEMToCustom()"><?php esc_html_e( '+ Zu eigenen Klassen hinzufügen', 'ecf-framework' ); ?></button>
            <button type="button" class="v2-btn v2-btn--ghost" onclick="ecfV2CopyBEM()"><?php esc_html_e( 'Kopieren', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>
      </div><!-- /generator tab -->

    </div><!-- /content -->
    <aside class="v2-aside">
      <div class="v2-as-head"><?php esc_html_e( 'Aktive Klassen', 'ecf-framework' ); ?></div>
      <div class="v2-as-row" title="<?php esc_attr_e( 'Vorgefertigte Layout- und Basis-Klassen', 'ecf-framework' ); ?>"><span class="v2-as-k"><?php esc_html_e( 'Starter', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $active_starter ); ?></span></div>
      <div class="v2-as-row" title="<?php esc_attr_e( 'Hilfsklassen für Abstände, Farben und Typografie', 'ecf-framework' ); ?>"><span class="v2-as-k"><?php esc_html_e( 'Utility', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $active_utility ); ?></span></div>
      <div class="v2-as-row" title="<?php esc_attr_e( 'Selbst angelegte BEM-Klassen', 'ecf-framework' ); ?>"><span class="v2-as-k"><?php esc_html_e( 'Eigene', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $active_custom ); ?></span></div>
      <div class="v2-as-row v2-as-row--total" title="<?php esc_attr_e( 'Summe aller Klassen die beim nächsten Sync zu Elementor übertragen werden', 'ecf-framework' ); ?>"><span class="v2-as-k"><?php esc_html_e( 'Gesamt (Sync)', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $active_total ); ?></span></div>
      <div class="v2-as-block">
        <?php
          $c_el_total = (int) ( $limit_snap['classes_total'] ?? 0 );
          $c_limit    = (int) ( $limit_snap['classes_limit'] ?? 100 );
          $c_foreign  = max( 0, $c_el_total - $active_total );
          $c_pct      = min( 100, (int) round( $c_el_total / max( 1, $c_limit ) * 100 ) );
        ?>
        <div class="v2-limit-overview">
          <div class="v2-limit-eyebrow"><?php esc_html_e( 'Klassen-Übersicht', 'ecf-framework' ); ?></div>
          <div class="v2-limit-hero">
            <span class="v2-limit-big"><?php echo esc_html( $c_el_total ); ?></span>
            <span class="v2-limit-of"><?php esc_html_e( 'von', 'ecf-framework' ); ?></span>
            <span class="v2-limit-big"><?php echo esc_html( $c_limit ); ?></span>
          </div>
          <div class="v2-limit-sub"><?php esc_html_e( 'Klassen verwendet', 'ecf-framework' ); ?></div>
          <div class="v2-limit-bar"><div class="v2-limit-fill v2-limit-fill--green" style="width:<?php echo esc_attr( $c_pct ); ?>%"></div></div>
          <div class="v2-limit-details">
            <div class="v2-limit-detail-item" title="<?php esc_attr_e( 'Aktive Klassen aus Layrix, die beim Sync zu Elementor übertragen werden', 'ecf-framework' ); ?>"><span><?php esc_html_e( 'Von Layrix', 'ecf-framework' ); ?></span><strong><?php echo esc_html( $active_total ); ?></strong></div>
            <div class="v2-limit-detail-item" title="<?php esc_attr_e( 'Klassen direkt in Elementor angelegt, nicht in Layrix verwaltet', 'ecf-framework' ); ?>"><span><?php esc_html_e( 'Nur in Elementor', 'ecf-framework' ); ?></span><strong><?php echo esc_html( $c_foreign ); ?></strong></div>
          </div>
        </div>
      </div>
    </aside>
  </div>
</div><!-- /classes page -->

<!-- ═══ PAGE: REZEPTE (COOKBOOK) ═══════════════════════════════════ -->
<div id="ecf-v2-page-cookbook" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Anwendung', 'ecf-framework' ); ?></span></div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph">
        <h1><?php esc_html_e( 'Anwendung', 'ecf-framework' ); ?></h1>
        <p><?php esc_html_e( 'Zum Kopieren: typische Zuordnungen von Layrix-Klassen und -Variablen zu Überschriften, Sections und Komponenten.', 'ecf-framework' ); ?></p>
      </div>

      <div class="v2-tabs">
        <button type="button" class="v2-tab v2-tab--on" data-v2-tab-group="cb" data-v2-tab="headings"><?php esc_html_e( 'Überschriften', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="cb" data-v2-tab="sections"><?php esc_html_e( 'Sections', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="cb" data-v2-tab="components"><?php esc_html_e( 'Komponenten', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="cb" data-v2-tab="layout"><?php esc_html_e( 'Layout', 'ecf-framework' ); ?></button>
      </div>

      <?php
      // Helper: Map a Layrix token name to a short human description (for tooltips).
      $token_label = function ( $var ) {
          $var = (string) $var;
          if ( strpos( $var, '--ecf-text-' )    === 0 ) return __( 'Schriftgröße',         'ecf-framework' );
          if ( strpos( $var, '--ecf-leading-' ) === 0 ) return __( 'Zeilenhöhe (Leading)', 'ecf-framework' );
          if ( strpos( $var, '--ecf-weight-' )  === 0 ) return __( 'Schriftstärke',        'ecf-framework' );
          if ( strpos( $var, '--ecf-tracking-' )=== 0 ) return __( 'Buchstabenabstand',    'ecf-framework' );
          if ( strpos( $var, '--ecf-space-' )   === 0 ) return __( 'Abstand',              'ecf-framework' );
          if ( strpos( $var, '--ecf-radius-' )  === 0 ) return __( 'Eckenradius',          'ecf-framework' );
          if ( strpos( $var, '--ecf-shadow-' )  === 0 ) return __( 'Schatten',             'ecf-framework' );
          if ( strpos( $var, '--ecf-color-' )   === 0 ) return __( 'Farbe',                'ecf-framework' );
          $map = [
              '--ecf-base-text-color'        => __( 'Basis-Textfarbe',           'ecf-framework' ),
              '--ecf-base-background-color'  => __( 'Basis-Hintergrundfarbe',    'ecf-framework' ),
              '--ecf-link-color'             => __( 'Link-Farbe',                'ecf-framework' ),
              '--ecf-focus-color'            => __( 'Fokus-Farbe',               'ecf-framework' ),
              '--ecf-focus-outline-width'    => __( 'Fokus-Rahmen-Breite',       'ecf-framework' ),
              '--ecf-focus-outline-offset'   => __( 'Fokus-Rahmen-Abstand',      'ecf-framework' ),
              '--ecf-container-boxed'        => __( 'Container-Breite (boxed)',  'ecf-framework' ),
              '--ecf-content-max-width'      => __( 'Lese-Maximum (Textbreite)', 'ecf-framework' ),
              '--ecf-base-body-text-size'    => __( 'Schriftgröße Fließtext',    'ecf-framework' ),
              '--ecf-base-body-font-weight'  => __( 'Schriftstärke Fließtext',   'ecf-framework' ),
              '--ecf-base-font-family'       => __( 'Schriftfamilie',            'ecf-framework' ),
              '--ecf-base-body-font-family'  => __( 'Schriftfamilie Fließtext',  'ecf-framework' ),
              '--ecf-heading-font-family'    => __( 'Schriftfamilie Überschriften', 'ecf-framework' ),
              'uppercase'                    => __( 'CSS text-transform: uppercase', 'ecf-framework' ),
          ];
          return $map[ $var ] ?? '';
      };

      // Helper: Render an Elementor recipe as a clickable card.
      // The whole card copies the class string when clicked.
      // Optional $tokens shows the CSS tokens the class maps to (mono line).
      $render_ele = function( $title, $desc, $widget, $classes, $tokens = [] ) use ( $token_label ) {
          unset( $widget );
          $cls = esc_attr( $classes );
          $tokens = array_filter( (array) $tokens );
          ?>
          <button type="button" class="v2-recipe v2-recipe--ele" data-v2-copy="<?php echo $cls; ?>">
            <span class="v2-recipe-title"><?php echo esc_html( $title ); ?></span>
            <?php if ( $desc ) : ?><span class="v2-recipe-desc"><?php echo esc_html( $desc ); ?></span><?php endif; ?>
            <?php if ( $tokens ) : ?>
              <span class="v2-recipe-tokens">
                <?php foreach ( $tokens as $i => $t ) :
                  if ( $i > 0 ) echo ' · ';
                  $label = $token_label( $t );
                ?><span<?php if ( $label ) : ?> data-v2-tip="<?php echo esc_attr( $label ); ?>"<?php endif; ?>><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
              </span>
            <?php endif; ?>
            <span class="v2-recipe-chip">
              <code><?php echo esc_html( $classes ); ?></code>
              <svg class="v2-recipe-chip-i" width="12" height="12" viewBox="0 0 13 13" fill="none" aria-hidden="true"><rect x="3" y="3" width="7.5" height="8" rx="1" stroke="currentColor" stroke-width="1.1"/><path d="M5 3V2h5v6.5h-1" stroke="currentColor" stroke-width="1.1"/></svg>
            </span>
          </button>
          <?php
      };

      // Helper: Per-tab hint banner explaining the Elementor workflow as 2 steps.
      $render_hint = function( $intro = '' ) {
          ?>
          <div class="v2-recipe-hint">
            <svg width="14" height="14" viewBox="0 0 13 13" fill="none" aria-hidden="true"><circle cx="6.5" cy="6.5" r="5.5" stroke="currentColor" stroke-width="1.1" opacity=".5"/><path d="M6.5 5.5v3M6.5 4.2v.1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
            <div>
              <?php if ( $intro ) : ?><div style="margin-bottom:6px"><?php echo esc_html( $intro ); ?></div><?php endif; ?>
              <ol class="v2-recipe-steps">
                <li><?php esc_html_e( 'Hier auf eine Karte klicken — die Klasse wird automatisch in die Zwischenablage kopiert.', 'ecf-framework' ); ?></li>
                <li>
                  <?php
                  printf(
                      /* translators: %s: name of the CSS classes field */
                      esc_html__( 'In Elementor das Widget anklicken und das Feld %s suchen — dort einfügen (Strg+V / ⌘+V).', 'ecf-framework' ),
                      '<strong>' . esc_html__( 'CSS-Klassen', 'ecf-framework' ) . '</strong>'
                  );
                  ?>
                </li>
              </ol>
            </div>
          </div>
          <?php
      };

      ?>

      <!-- ── Überschriften ────────────────────────────────────── -->
      <div id="v2-cb-headings" class="v2-tp v2-tp--on">

        <?php $render_hint(); ?>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Überschriften & Text-Stile', 'ecf-framework' ); ?></div>
          <div class="v2-recipe-grid">
            <?php $render_ele( __( 'H1 Hero', 'ecf-framework' ),       __( 'Größte Überschrift, einmal pro Seite', 'ecf-framework' ), __( 'Heading-Widget', 'ecf-framework' ), 'ecf-heading-1', [ '--ecf-text-4xl', '--ecf-leading-tight', '--ecf-weight-bold' ] ); ?>
            <?php $render_ele( __( 'H2 Abschnitt', 'ecf-framework' ),  __( 'Hauptüberschrift einer Section', 'ecf-framework' ),       __( 'Heading-Widget', 'ecf-framework' ), 'ecf-heading-2', [ '--ecf-text-3xl', '--ecf-leading-tight', '--ecf-weight-bold' ] ); ?>
            <?php $render_ele( __( 'H3 Unterabschnitt', 'ecf-framework' ), __( 'Untergeordnete Überschrift', 'ecf-framework' ),       __( 'Heading-Widget', 'ecf-framework' ), 'ecf-heading-3', [ '--ecf-text-2xl', '--ecf-leading-snug', '--ecf-weight-semibold' ] ); ?>
            <?php $render_ele( __( 'H4 Detail', 'ecf-framework' ),     __( 'Kleinere Überschrift, z. B. in Cards', 'ecf-framework' ), __( 'Heading-Widget', 'ecf-framework' ), 'ecf-heading-4', [ '--ecf-text-xl', '--ecf-leading-snug', '--ecf-weight-semibold' ] ); ?>
            <?php $render_ele( __( 'H5 Klein', 'ecf-framework' ),      __( 'Kleinste Überschrift', 'ecf-framework' ),                  __( 'Heading-Widget', 'ecf-framework' ), 'ecf-heading-5', [ '--ecf-text-l', '--ecf-leading-normal', '--ecf-weight-semibold' ] ); ?>
            <?php $render_ele( __( 'Eyebrow / Overline', 'ecf-framework' ), __( 'Kleiner Label-Text über einer Überschrift', 'ecf-framework' ), __( 'Text-Widget', 'ecf-framework' ), 'ecf-overline', [ '--ecf-text-xs', '--ecf-tracking-widest', 'uppercase' ] ); ?>
            <?php $render_ele( __( 'Großer Fließtext', 'ecf-framework' ), __( 'Lead-Absatz, etwas größer als normaler Text', 'ecf-framework' ), __( 'Text-Editor-Widget', 'ecf-framework' ), 'ecf-body-l', [ '--ecf-text-l', '--ecf-leading-relaxed' ] ); ?>
            <?php $render_ele( __( 'Caption / Bildunterschrift', 'ecf-framework' ), __( 'Klein und unauffällig', 'ecf-framework' ), __( 'Text-Widget', 'ecf-framework' ), 'ecf-caption', [ '--ecf-text-xs', '--ecf-leading-snug' ] ); ?>
          </div>
        </div>


      </div><!-- /headings -->

      <!-- ── Sections ────────────────────────────────────────── -->
      <div id="v2-cb-sections" class="v2-tp">

        <?php $render_hint( __( 'Eine Section heißt in Elementor Container oder Section.', 'ecf-framework' ) ); ?>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Section-Stile', 'ecf-framework' ); ?></div>
          <div class="v2-recipe-grid">
            <?php $render_ele( __( 'Standard-Section', 'ecf-framework' ),  __( 'Padding und Container automatisch', 'ecf-framework' ),  __( 'Container / Section', 'ecf-framework' ), 'ecf-section' ); ?>
            <?php $render_ele( __( 'Innerer Wrapper', 'ecf-framework' ),   __( 'Auf max. Container-Breite zentriert', 'ecf-framework' ), __( 'Inneres Container-Widget', 'ecf-framework' ), 'ecf-section__inner' ); ?>
            <?php $render_ele( __( 'Hero-Section', 'ecf-framework' ),      __( 'Höhere, prominente Eingangs-Section', 'ecf-framework' ), __( 'Container / Section', 'ecf-framework' ), 'ecf-hero' ); ?>
            <?php $render_ele( __( 'Hero-Inhalt', 'ecf-framework' ),       __( 'Inhaltsbox innerhalb der Hero', 'ecf-framework' ),       __( 'Inneres Container-Widget', 'ecf-framework' ), 'ecf-hero__content' ); ?>
            <?php $render_ele( __( 'Dunkle Section', 'ecf-framework' ),    __( 'Variante mit dunklem Hintergrund', 'ecf-framework' ),     __( 'Container / Section', 'ecf-framework' ), 'ecf-section ecf-section--dark' ); ?>
            <?php $render_ele( __( 'Akzent-Section', 'ecf-framework' ),    __( 'Hintergrund in Akzentfarbe', 'ecf-framework' ),           __( 'Container / Section', 'ecf-framework' ), 'ecf-section ecf-section--accent' ); ?>
          </div>
        </div>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Beispiele — so sehen die Section-Varianten aus', 'ecf-framework' ); ?></div>

          <div class="v2-cb-demo">
            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label">.ecf-section</div>
              <div class="v2-cb-section">
                <div class="v2-cb-section__inner">
                  <div class="v2-cb-line v2-cb-line--head"></div>
                  <div class="v2-cb-line"></div>
                  <div class="v2-cb-line" style="width:70%"></div>
                </div>
              </div>
              <div class="v2-cb-demo-note"><?php esc_html_e( 'Standard-Padding oben/unten, Inhalt zentriert.', 'ecf-framework' ); ?></div>
            </div>

            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label">.ecf-section__inner</div>
              <div class="v2-cb-section v2-cb-section--show-inner">
                <div class="v2-cb-section__inner v2-cb-section__inner--highlight">
                  <div class="v2-cb-line v2-cb-line--head"></div>
                  <div class="v2-cb-line"></div>
                </div>
              </div>
              <div class="v2-cb-demo-note"><?php esc_html_e( 'Innere Box, auf Container-Breite zentriert (markiert).', 'ecf-framework' ); ?></div>
            </div>

            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label">.ecf-hero</div>
              <div class="v2-cb-section v2-cb-section--hero">
                <div class="v2-cb-section__inner">
                  <div class="v2-cb-eyebrow">●&nbsp;<?php esc_html_e( 'Eyebrow', 'ecf-framework' ); ?></div>
                  <div class="v2-cb-line v2-cb-line--hero"></div>
                  <div class="v2-cb-line" style="width:80%"></div>
                  <div class="v2-cb-actions">
                    <span class="v2-cb-btn v2-cb-btn--primary"></span>
                    <span class="v2-cb-btn v2-cb-btn--ghost"></span>
                  </div>
                </div>
              </div>
              <div class="v2-cb-demo-note"><?php esc_html_e( 'Höher und prominenter — typisch oben auf der Seite.', 'ecf-framework' ); ?></div>
            </div>

            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label">.ecf-section.ecf-section--dark</div>
              <div class="v2-cb-section v2-cb-section--dark">
                <div class="v2-cb-section__inner">
                  <div class="v2-cb-line v2-cb-line--head v2-cb-line--light"></div>
                  <div class="v2-cb-line v2-cb-line--light"></div>
                </div>
              </div>
              <div class="v2-cb-demo-note"><?php esc_html_e( 'Variante mit dunklem Hintergrund — z. B. für Footer-Bereiche.', 'ecf-framework' ); ?></div>
            </div>

            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label">.ecf-section.ecf-section--accent</div>
              <div class="v2-cb-section v2-cb-section--accent">
                <div class="v2-cb-section__inner">
                  <div class="v2-cb-line v2-cb-line--head v2-cb-line--light"></div>
                  <div class="v2-cb-line v2-cb-line--light"></div>
                </div>
              </div>
              <div class="v2-cb-demo-note"><?php esc_html_e( 'Hintergrund in Akzentfarbe — für Call-to-Action-Bereiche.', 'ecf-framework' ); ?></div>
            </div>
          </div>
        </div>

      </div><!-- /sections -->

      <!-- ── Komponenten ─────────────────────────────────────── -->
      <div id="v2-cb-components" class="v2-tp">

        <?php $render_hint( __( 'Mehrere Klassen kannst Du mit Leerzeichen kombinieren.', 'ecf-framework' ) ); ?>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Buttons', 'ecf-framework' ); ?></div>
          <div class="v2-recipe-grid">
            <?php $render_ele( __( 'Primärer Button', 'ecf-framework' ),  __( 'Hauptaktion — eine pro Section', 'ecf-framework' ),    __( 'Button-Widget', 'ecf-framework' ), 'ecf-button ecf-button--primary' ); ?>
            <?php $render_ele( __( 'Sekundärer Button', 'ecf-framework' ),__( 'Nebenaktion', 'ecf-framework' ),                       __( 'Button-Widget', 'ecf-framework' ), 'ecf-button ecf-button--secondary' ); ?>
            <?php $render_ele( __( 'Ghost Button', 'ecf-framework' ),     __( 'Dezent, nur Border', 'ecf-framework' ),                __( 'Button-Widget', 'ecf-framework' ), 'ecf-button ecf-button--ghost' ); ?>
            <?php $render_ele( __( 'Großer Button', 'ecf-framework' ),    __( 'Mehr Padding und Schrift', 'ecf-framework' ),          __( 'Button-Widget', 'ecf-framework' ), 'ecf-button ecf-button--primary ecf-button--large' ); ?>
            <?php $render_ele( __( 'Link-Button', 'ecf-framework' ),      __( 'Sieht aus wie ein Text-Link', 'ecf-framework' ),       __( 'Button-Widget', 'ecf-framework' ), 'ecf-button ecf-button--link' ); ?>
          </div>
        </div>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Cards', 'ecf-framework' ); ?></div>
          <div class="v2-sh2" style="margin-bottom:12px"><?php esc_html_e( 'Eine Card baust Du aus einem Container mit Bild, Heading, Text und Button — Klasse am jeweils passenden Element.', 'ecf-framework' ); ?></div>
          <div class="v2-recipe-grid">
            <?php $render_ele( __( 'Card-Wrapper', 'ecf-framework' ),  __( 'Padding, Hintergrund, Schatten', 'ecf-framework' ), __( 'Container / Element', 'ecf-framework' ), 'ecf-card' ); ?>
            <?php $render_ele( __( 'Card-Body', 'ecf-framework' ),     __( 'Inhaltsbereich innerhalb der Card', 'ecf-framework' ), __( 'Inneres Container-Widget', 'ecf-framework' ), 'ecf-card__body' ); ?>
            <?php $render_ele( __( 'Card-Titel', 'ecf-framework' ),    __( 'Mit Heading-Größe kombinieren', 'ecf-framework' ),  __( 'Heading-Widget in der Card', 'ecf-framework' ), 'ecf-card__title ecf-heading-4' ); ?>
          </div>
        </div>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Badges', 'ecf-framework' ); ?></div>
          <div class="v2-recipe-grid">
            <?php $render_ele( __( 'Badge primär', 'ecf-framework' ),   '', __( 'Text-Widget', 'ecf-framework' ), 'ecf-badge ecf-badge--primary' ); ?>
            <?php $render_ele( __( 'Badge sekundär', 'ecf-framework' ), '', __( 'Text-Widget', 'ecf-framework' ), 'ecf-badge ecf-badge--secondary' ); ?>
          </div>
        </div>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Beispiele', 'ecf-framework' ); ?></div>
          <div class="v2-cb-demo">
            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label"><?php esc_html_e( 'Buttons', 'ecf-framework' ); ?></div>
              <div class="v2-cb-demo-stage" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
                <span class="v2-cb-btn-real v2-cb-btn-real--primary"><?php esc_html_e( 'Primär', 'ecf-framework' ); ?></span>
                <span class="v2-cb-btn-real v2-cb-btn-real--secondary"><?php esc_html_e( 'Sekundär', 'ecf-framework' ); ?></span>
                <span class="v2-cb-btn-real v2-cb-btn-real--ghost"><?php esc_html_e( 'Ghost', 'ecf-framework' ); ?></span>
                <span class="v2-cb-btn-real v2-cb-btn-real--primary v2-cb-btn-real--large"><?php esc_html_e( 'Großer Primär', 'ecf-framework' ); ?></span>
                <span class="v2-cb-btn-real v2-cb-btn-real--link"><?php esc_html_e( 'Link', 'ecf-framework' ); ?></span>
              </div>
            </div>

            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label"><?php esc_html_e( 'Card', 'ecf-framework' ); ?></div>
              <div class="v2-cb-demo-stage">
                <div class="v2-cb-card-real">
                  <div class="v2-cb-card-real__media"></div>
                  <div class="v2-cb-card-real__body">
                    <div class="v2-cb-card-real__title"><?php esc_html_e( 'Karten-Titel', 'ecf-framework' ); ?></div>
                    <div class="v2-cb-card-real__desc"><?php esc_html_e( 'Kurze Beschreibung des Karteninhalts.', 'ecf-framework' ); ?></div>
                    <span class="v2-cb-btn-real v2-cb-btn-real--ghost" style="font-size:var(--v2-ui-base-fs, 13px);padding:4px 10px"><?php esc_html_e( 'Mehr', 'ecf-framework' ); ?></span>
                  </div>
                </div>
              </div>
            </div>

            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label"><?php esc_html_e( 'Badges', 'ecf-framework' ); ?></div>
              <div class="v2-cb-demo-stage" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                <span class="v2-cb-badge-real v2-cb-badge-real--primary"><?php esc_html_e( 'Neu', 'ecf-framework' ); ?></span>
                <span class="v2-cb-badge-real v2-cb-badge-real--secondary"><?php esc_html_e( 'Beta', 'ecf-framework' ); ?></span>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /components -->

      <!-- ── Layout ─────────────────────────────────────────── -->
      <div id="v2-cb-layout" class="v2-tp">

        <?php $render_hint( __( 'Layout-Klassen kommen meist auf einen Container.', 'ecf-framework' ) ); ?>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Container', 'ecf-framework' ); ?></div>
          <div class="v2-recipe-grid">
            <?php $render_ele( __( 'Boxed-Container', 'ecf-framework' ), __( 'Inhalt zentriert auf Deine Container-Breite', 'ecf-framework' ), __( 'Container / Section', 'ecf-framework' ), 'ecf-container-boxed' ); ?>
          </div>
        </div>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Grid', 'ecf-framework' ); ?></div>
          <div class="v2-sh2" style="margin-bottom:12px"><?php esc_html_e( 'Auf dem Container, der die Spalten enthalten soll.', 'ecf-framework' ); ?></div>
          <div class="v2-recipe-grid">
            <?php $render_ele( __( '2 Spalten', 'ecf-framework' ),       '', __( 'Container-Widget', 'ecf-framework' ), 'ecf-grid--2' ); ?>
            <?php $render_ele( __( '3 Spalten', 'ecf-framework' ),       '', __( 'Container-Widget', 'ecf-framework' ), 'ecf-grid--3' ); ?>
            <?php $render_ele( __( '4 Spalten', 'ecf-framework' ),       '', __( 'Container-Widget', 'ecf-framework' ), 'ecf-grid--4' ); ?>
          </div>
        </div>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Stack — vertikale Abstände', 'ecf-framework' ); ?></div>
          <div class="v2-sh2" style="margin-bottom:12px"><?php esc_html_e( 'Auf einem Container, der Elemente untereinander stapelt — sorgt für gleichmäßigen Abstand.', 'ecf-framework' ); ?></div>
          <div class="v2-recipe-grid">
            <?php $render_ele( __( 'Stack XS', 'ecf-framework' ), __( 'Sehr enger Abstand', 'ecf-framework' ),  __( 'Container-Widget', 'ecf-framework' ), 'ecf-stack--xs' ); ?>
            <?php $render_ele( __( 'Stack S',  'ecf-framework' ), __( 'Enger Abstand', 'ecf-framework' ),       __( 'Container-Widget', 'ecf-framework' ), 'ecf-stack--s' ); ?>
            <?php $render_ele( __( 'Stack M',  'ecf-framework' ), __( 'Standard-Abstand', 'ecf-framework' ),    __( 'Container-Widget', 'ecf-framework' ), 'ecf-stack--m' ); ?>
            <?php $render_ele( __( 'Stack L',  'ecf-framework' ), __( 'Großer Abstand', 'ecf-framework' ),      __( 'Container-Widget', 'ecf-framework' ), 'ecf-stack--l' ); ?>
            <?php $render_ele( __( 'Stack XL', 'ecf-framework' ), __( 'Sehr großer Abstand', 'ecf-framework' ), __( 'Container-Widget', 'ecf-framework' ), 'ecf-stack--xl' ); ?>
          </div>
        </div>

        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Beispiele', 'ecf-framework' ); ?></div>

          <div class="v2-cb-demo">
            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label">.ecf-container-boxed</div>
              <div class="v2-cb-demo-stage">
                <div class="v2-cb-container-demo">
                  <div class="v2-cb-container-demo__inner">
                    <div class="v2-cb-line v2-cb-line--head"></div>
                    <div class="v2-cb-line"></div>
                  </div>
                </div>
              </div>
              <div class="v2-cb-demo-note"><?php esc_html_e( 'Inhalt zentriert mit Seitenrändern.', 'ecf-framework' ); ?></div>
            </div>

            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label">.ecf-grid--2 / 3 / 4</div>
              <div class="v2-cb-demo-stage" style="display:flex;flex-direction:column;gap:8px">
                <div class="v2-cb-grid-demo v2-cb-grid-demo--2"><span></span><span></span></div>
                <div class="v2-cb-grid-demo v2-cb-grid-demo--3"><span></span><span></span><span></span></div>
                <div class="v2-cb-grid-demo v2-cb-grid-demo--4"><span></span><span></span><span></span><span></span></div>
              </div>
              <div class="v2-cb-demo-note"><?php esc_html_e( 'Spalten-Anzahl im Container.', 'ecf-framework' ); ?></div>
            </div>

            <div class="v2-cb-demo-item">
              <div class="v2-cb-demo-label">.ecf-stack--xs … xl</div>
              <div class="v2-cb-demo-stage" style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px">
                <div class="v2-cb-stack-demo v2-cb-stack-demo--xs"><span></span><span></span><span></span><div>xs</div></div>
                <div class="v2-cb-stack-demo v2-cb-stack-demo--s"><span></span><span></span><span></span><div>s</div></div>
                <div class="v2-cb-stack-demo v2-cb-stack-demo--m"><span></span><span></span><span></span><div>m</div></div>
                <div class="v2-cb-stack-demo v2-cb-stack-demo--l"><span></span><span></span><span></span><div>l</div></div>
                <div class="v2-cb-stack-demo v2-cb-stack-demo--xl"><span></span><span></span><span></span><div>xl</div></div>
              </div>
              <div class="v2-cb-demo-note"><?php esc_html_e( 'Vertikaler Abstand zwischen gestapelten Elementen.', 'ecf-framework' ); ?></div>
            </div>
          </div>
        </div>

      </div><!-- /layout -->

    </div>
  </div>
</div><!-- /cookbook page -->

<!-- ═══ PAGE: SYNC & EXPORT ════════════════════════════════════════ -->
<div id="ecf-v2-page-sync" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Sync & Export', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <button type="button" class="v2-btn v2-btn--primary" data-v2-sync>
          <svg width="12" height="12" viewBox="0 0 13 13" fill="none"><path d="M6.5 2v7M4 7l2.5 2.5L9 7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
          <?php esc_html_e( 'Mit Elementor synchronisieren', 'ecf-framework' ); ?>
        </button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Sync & Export', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Alle Tokens und Klassen mit Elementor synchronisieren oder für die Versionierung exportieren.', 'ecf-framework' ); ?></p></div>
      <div class="v2-info-callout">
        <div class="v2-info-callout-row"><strong><?php esc_html_e( 'Speichern', 'ecf-framework' ); ?></strong> <?php esc_html_e( '→ sichert deine Einstellungen in der Datenbank (passiert automatisch nach jeder Änderung).', 'ecf-framework' ); ?></div>
        <div class="v2-info-callout-row"><strong><?php esc_html_e( 'Sync mit Elementor', 'ecf-framework' ); ?></strong> <?php esc_html_e( '→ schreibt CSS-Variablen und Klassen in den Elementor-Speicher. Danach müssen offene Elementor-Tabs neu geladen werden.', 'ecf-framework' ); ?></div>
      </div>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Zum Sync bereit', 'ecf-framework' ); ?></div>
        <div class="v2-sync-stat">
          <div class="v2-ss-item">
            <div class="v2-ss-num"><?php echo esc_html( $sync_var_count ); ?><span class="v2-ss-limit">/<?php echo esc_html( $limit_snap['variables_limit'] ?? 1000 ); ?></span></div>
            <div class="v2-ss-lbl">CSS-Variablen</div>
          </div>
          <div class="v2-ss-item">
            <div class="v2-ss-num"><?php echo esc_html( $sync_cls_count ); ?><span class="v2-ss-limit">/<?php echo esc_html( $limit_snap['classes_limit'] ?? 100 ); ?></span></div>
            <div class="v2-ss-lbl"><?php esc_html_e( 'Klassen', 'ecf-framework' ); ?></div>
          </div>
        </div>
      </div>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Export / Import', 'ecf-framework' ); ?></div>
        <div style="border:1px solid var(--v2-border);border-radius:7px;overflow:hidden">
          <!-- JSON Export -->
          <div class="v2-export-row">
            <div><div class="v2-exp-name"><?php esc_html_e( 'JSON-Export', 'ecf-framework' ); ?></div><div class="v2-exp-desc"><?php esc_html_e( 'Alle Einstellungen als strukturiertes JSON für Versionierung und Wiederherstellung.', 'ecf-framework' ); ?></div></div>
            <button type="button" class="v2-btn v2-btn--ghost" onclick="document.getElementById('ecf-export-form').submit()">↓ <?php esc_html_e( 'Herunterladen', 'ecf-framework' ); ?></button>
          </div>
          <!-- CSS Export -->
          <div class="v2-export-row">
            <div>
              <div class="v2-exp-name"><?php esc_html_e( 'CSS-Variablen', 'ecf-framework' ); ?></div>
              <div class="v2-exp-desc">:root { --ecf-… } <?php esc_html_e( 'Block für externe Projekte oder als Fallback wenn das Plugin deaktiviert wird.', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0">
              <button type="button" class="v2-btn v2-btn--ghost" onclick="document.getElementById('v2-css-drawer').style.display='flex'">&lt;/&gt; <?php esc_html_e( 'Anzeigen', 'ecf-framework' ); ?></button>
              <a class="v2-btn v2-btn--ghost" href="<?php echo esc_attr( $generated_css_download ); ?>" download="layrix-generated.css">↓ <?php esc_html_e( 'Herunterladen', 'ecf-framework' ); ?></a>
            </div>
          </div>
          <!-- Import -->
          <div class="v2-export-row">
            <div>
              <div class="v2-exp-name"><?php esc_html_e( 'JSON importieren', 'ecf-framework' ); ?></div>
              <div class="v2-exp-desc"><?php esc_html_e( 'Zuvor exportierte Einstellungsdatei wiederherstellen.', 'ecf-framework' ); ?></div>
              <div id="v2-import-preview" style="display:none;margin-top:6px;font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text2);font-family:var(--v2-mono)"></div>
            </div>
            <label for="v2-import-file" class="v2-btn v2-btn--ghost" style="cursor:pointer">
                ↑ <?php esc_html_e( 'Choose file', 'ecf-framework' ); ?>
              </label>
              <button type="button" class="v2-btn v2-btn--primary" id="v2-import-submit" style="display:none"><?php esc_html_e( 'Select sections →', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Wartung', 'ecf-framework' ); ?></div>
        <div style="border:1px solid var(--v2-border);border-radius:7px;overflow:hidden">
          <div class="v2-export-row">
            <div>
              <div class="v2-exp-name"><?php esc_html_e( 'Reset Classes in Elementor', 'ecf-framework' ); ?></div>
              <div class="v2-exp-desc"><?php esc_html_e( 'Remove Layrix classes from Elementor so they can be re-synced cleanly.', 'ecf-framework' ); ?></div>
            </div>
            <button type="button" class="v2-btn v2-btn--ghost" style="color:var(--v2-amber)" onclick="if(confirm('<?php esc_attr_e( 'Really remove Layrix classes from Elementor?', 'ecf-framework' ); ?>')) document.getElementById('ecf-class-cleanup-form').submit()"><?php esc_html_e( 'Clean Up', 'ecf-framework' ); ?></button>
          </div>
          <div class="v2-export-row">
            <div>
              <div class="v2-exp-name"><?php esc_html_e( 'Remove All Layrix Data from Elementor', 'ecf-framework' ); ?></div>
              <div class="v2-exp-desc"><?php esc_html_e( 'Removes all ECF variables and classes from Elementor — cannot be undone.', 'ecf-framework' ); ?></div>
            </div>
            <button type="button" class="v2-btn v2-btn--ghost" style="color:var(--v2-danger)" onclick="if(confirm('<?php esc_attr_e( 'Really remove ALL Layrix data from Elementor? This cannot be undone.', 'ecf-framework' ); ?>')) document.getElementById('ecf-native-cleanup-form').submit()"><?php esc_html_e( 'Full Cleanup', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Nach dem Sync', 'ecf-framework' ); ?></div>
        <ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:5px;font-size:12px;color:var(--v2-text2)">
          <li><?php esc_html_e( 'Offene Elementor-Tabs einmal neu laden nach einem manuellen Sync.', 'ecf-framework' ); ?></li>
          <li><?php esc_html_e( 'Auto-Sync aktivieren, damit Layrix Änderungen direkt nach dem Speichern überträgt.', 'ecf-framework' ); ?></li>
          <li><?php esc_html_e( 'Wenn Änderungen noch nicht sichtbar sind: Elementor-Caches leeren und Editor neu öffnen.', 'ecf-framework' ); ?></li>
        </ul>
      </div>

      <!-- CSS Drawer -->
      <div id="v2-css-drawer" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.6);align-items:flex-end;justify-content:center" onclick="if(event.target===this)this.style.display='none'">
        <div style="background:var(--v2-bg2,#1e293b);border-radius:12px 12px 0 0;width:100%;max-width:860px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 -8px 32px rgba(0,0,0,.4)">
          <!-- Header -->
          <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--v2-border)">
            <div>
              <span style="font-size:13px;font-weight:600;color:var(--v2-text)"><?php esc_html_e( 'Generiertes CSS', 'ecf-framework' ); ?></span>
              <span style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3);margin-left:8px"><?php esc_html_e( 'Diesen Code brauchst du, wenn du das Plugin deaktivierst — in WordPress unter Design → Zusätzliches CSS einfügen.', 'ecf-framework' ); ?></span>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
              <button type="button" class="v2-btn v2-btn--ghost" data-v2-export-css>↓ <?php esc_html_e( 'Kopieren', 'ecf-framework' ); ?></button>
              <a class="v2-btn v2-btn--ghost" href="<?php echo esc_attr( $generated_css_download ); ?>" download="layrix-generated.css">↓ <?php esc_html_e( 'Herunterladen', 'ecf-framework' ); ?></a>
              <button type="button" onclick="document.getElementById('v2-css-drawer').style.display='none'" style="background:none;border:none;color:var(--v2-text3);font-size:18px;cursor:pointer;line-height:1;padding:2px 4px" title="<?php esc_attr_e( 'Schließen', 'ecf-framework' ); ?>">✕</button>
            </div>
          </div>
          <!-- Code -->
          <pre style="margin:0;padding:16px 18px;overflow-y:auto;font-family:var(--v2-mono,monospace);font-size:11.5px;line-height:1.6;color:#94a3b8;flex:1;white-space:pre-wrap;word-break:break-all"><?php echo esc_html( $generated_css ); ?></pre>
        </div>
      </div>

    </div>
    <aside class="v2-aside">
      <div class="v2-as-head"><?php esc_html_e( 'Nächster Sync', 'ecf-framework' ); ?></div>
      <div class="v2-as-row"><span class="v2-as-k">CSS-Variablen</span><span class="v2-as-v"><?php echo esc_html( $sync_var_count ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k"><?php esc_html_e( 'Klassen', 'ecf-framework' ); ?></span><span class="v2-as-v"><?php echo esc_html( $sync_cls_count ); ?></span></div>
      <div class="v2-as-row"><span class="v2-as-k">Plugin</span><span class="v2-as-v">v<?php echo esc_html( $ver ); ?></span></div>
      <div class="v2-as-block">
        <div class="v2-as-head"><?php esc_html_e( 'Aktuell in Elementor', 'ecf-framework' ); ?></div>
        <p style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3);margin:0 0 6px"><?php esc_html_e( 'Stand vor dem letzten Sync', 'ecf-framework' ); ?></p>
        <?php
        $vars_pct = $limit_snap['variables_limit'] > 0 ? $limit_snap['variables_total'] / $limit_snap['variables_limit'] : 0;
        $cls_pct  = $limit_snap['classes_limit']   > 0 ? $limit_snap['classes_total']   / $limit_snap['classes_limit']   : 0;
        $vars_mod = $vars_pct >= 0.9 ? ' v2-as-v--danger' : ( $vars_pct >= 0.7 ? ' v2-as-v--warn' : '' );
        $cls_mod  = $cls_pct  >= 0.9 ? ' v2-as-v--danger' : ( $cls_pct  >= 0.7 ? ' v2-as-v--warn' : '' );
        ?>
        <div class="v2-as-row">
          <span class="v2-as-k">CSS-Variablen</span>
          <span class="v2-as-v<?php echo $vars_mod; ?>"><?php echo esc_html( $limit_snap['variables_total'] . ' / ' . $limit_snap['variables_limit'] ); ?></span>
        </div>
        <div class="v2-as-row">
          <span class="v2-as-k"><?php esc_html_e( 'Klassen', 'ecf-framework' ); ?></span>
          <span class="v2-as-v<?php echo $cls_mod; ?>"><?php echo esc_html( $limit_snap['classes_total'] . ' / ' . $limit_snap['classes_limit'] ); ?></span>
        </div>
      </div>
      <div class="v2-as-block">
        <div class="v2-as-head"><?php esc_html_e( 'Auto-Sync', 'ecf-framework' ); ?></div>
        <div class="v2-sr" style="padding:0">
          <div><div class="v2-sl"><?php esc_html_e( 'Enable Auto-Sync', 'ecf-framework' ); ?></div></div>
          <label class="v2-tog-label">
            <input type="checkbox"
                   class="v2-tog-cb"
                   name="<?php echo esc_attr( $opt ); ?>[elementor_auto_sync_enabled]"
                   value="1"
                   <?php checked( ! empty( $settings['elementor_auto_sync_enabled'] ) ); ?>>
            <span class="v2-tog<?php echo ! empty( $settings['elementor_auto_sync_enabled'] ) ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
          </label>
        </div>
      </div>
    </aside>
  </div>
</div><!-- /sync page -->

<!-- ═══ PAGE: STARTHILFE ════════════════════════════════════════════ -->
<div id="ecf-v2-page-help" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Erste Schritte', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <button type="button" class="v2-btn v2-btn--ghost" data-ecf-open-changelog-modal><?php esc_html_e( 'Changelog', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Erste Schritte', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Schritt für Schritt zum fertigen Design-System — von den Basiseinstellungen bis zum ersten Sync.', 'ecf-framework' ); ?></p></div>

      <div class="v2-tabs">
        <button type="button" class="v2-tab v2-tab--on" data-v2-tab-group="hl" data-v2-tab="presets">🎨 <?php esc_html_e( 'Stil-Presets', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="hl" data-v2-tab="start">🚀 <?php esc_html_e( 'Erste Schritte', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="hl" data-v2-tab="health">🩺 <?php esc_html_e( 'Design-Gesundheit', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="hl" data-v2-tab="recs">💡 <?php esc_html_e( 'Smart Empfehlungen', 'ecf-framework' ); ?></button>
      </div>

      <!-- Tab: Stil-Presets -->
      <div id="v2-hl-presets" class="v2-tp v2-tp--on">
        <div class="v2-sec">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap">
            <div class="v2-tabs v2-tabs--inner" style="flex:1;min-width:0">
              <button type="button" class="v2-tab v2-tab--on" data-v2-preset-filter="alle"><?php esc_html_e( 'Alle', 'ecf-framework' ); ?> <span class="v2-tc" id="v2-preset-all-count"><?php echo count( $style_presets ) + count( $custom_presets ); ?></span></button>
              <button type="button" class="v2-tab" data-v2-preset-filter="business"><?php esc_html_e( 'Business', 'ecf-framework' ); ?></button>
              <button type="button" class="v2-tab" data-v2-preset-filter="kreativ"><?php esc_html_e( 'Kreativ', 'ecf-framework' ); ?></button>
              <button type="button" class="v2-tab" data-v2-preset-filter="produkt"><?php esc_html_e( 'Produkt', 'ecf-framework' ); ?></button>
              <button type="button" class="v2-tab" data-v2-preset-filter="persoenlich"><?php esc_html_e( 'Persönlich', 'ecf-framework' ); ?></button>
              <button type="button" class="v2-tab" data-v2-preset-filter="eigene"><?php esc_html_e( 'Eigene', 'ecf-framework' ); ?> <span class="v2-tc" id="v2-custom-preset-count"><?php echo count( $custom_presets ); ?></span></button>
            </div>
            <button type="button" class="v2-btn v2-btn--ghost" style="flex-shrink:0" id="v2-save-custom-preset">
              + <?php esc_html_e( 'Aktuellen Stand speichern', 'ecf-framework' ); ?>
            </button>
          </div>
          <div class="v2-preset-grid" id="v2-preset-grid">
            <?php foreach ( $style_presets as $preset ) :
              $gf = $preset['google_fonts'] ?? [];
              $gf_heading = $gf['heading'] ?? '';
              $gf_body    = $gf['body'] ?? '';
              $body_size  = $preset['preset']['general']['base_body_text_size'] ?? '16px';
              $info_data = [];
              if ( $gf_heading ) $info_data['heading'] = $gf_heading;
              if ( $gf_body )    $info_data['body']    = $gf_body . ' · ' . $body_size;
              $info_data['primary'] = $preset['preview']['primary'] ?? '';
              $info_data['accent']  = $preset['preview']['accent']  ?? '';
              $info_data['desc']    = $preset['description'] ?? '';
            ?>
            <?php
              $p_colors  = $preset['preset']['colors']  ?? [];
              $p_general = $preset['preset']['general'] ?? [];
              $color_keys = function ( $name ) use ( $p_colors ) {
                  foreach ( $p_colors as $row ) {
                      if ( ( $row['name'] ?? null ) === $name ) {
                          return $row['value'] ?? '';
                      }
                  }
                  return $p_colors[ $name ] ?? '';
              };
              $swatches = [
                  [ __( 'Primary',          'ecf-framework' ), $color_keys( 'primary' )                         ],
                  [ __( 'Secondary',        'ecf-framework' ), $color_keys( 'secondary' )                       ],
                  [ __( 'Accent',           'ecf-framework' ), $color_keys( 'accent' )                          ],
                  [ __( 'Surface',          'ecf-framework' ), $color_keys( 'surface' )                         ],
                  [ __( 'Text',             'ecf-framework' ), $p_general['base_text_color']       ?? ''        ],
                  [ __( 'Background',       'ecf-framework' ), $p_general['base_background_color'] ?? ''        ],
                  [ __( 'Link',             'ecf-framework' ), $p_general['link_color']            ?? ''        ],
              ];
            ?>
            <div class="v2-preset-card" data-category="<?php echo esc_attr( $preset['category'] ); ?>">
              <div class="v2-preset-swatch" style="background:<?php echo esc_attr( $preset['preview']['background'] ); ?>">
                <div style="display:flex;gap:4px;margin-bottom:8px;flex-wrap:wrap">
                  <?php foreach ( $swatches as $sw ) :
                      $label = $sw[0];
                      $value = $sw[1];
                      if ( $value === '' ) continue;
                  ?>
                  <span style="width:14px;height:14px;border-radius:3px;background:<?php echo esc_attr( $value ); ?>;border:1px solid rgba(0,0,0,.08)" title="<?php echo esc_attr( $label . ': ' . $value ); ?>"></span>
                  <?php endforeach; ?>
                </div>
                <div style="font-family:<?php echo esc_attr( $preset['heading_font_stack'] ); ?>;font-size:13px;font-weight:700;line-height:1.3;color:#111"><?php echo esc_html( $preset['heading_sample'] ); ?></div>
                <div style="font-family:<?php echo esc_attr( $preset['body_font_stack'] ); ?>;font-size:var(--v2-btn-fs, 12px);margin-top:3px;color:#555;line-height:1.4"><?php echo esc_html( $preset['body_sample'] ); ?></div>
              </div>
              <div class="v2-preset-body">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px">
                  <span class="v2-preset-tone"><?php echo esc_html( $preset['tone'] ); ?></span>
                  <span class="v2-preset-title"><?php echo esc_html( $preset['title'] ); ?></span>
                  <button type="button" class="v2-preset-info-btn" data-v2-preset-info="<?php echo esc_attr( wp_json_encode( $info_data ) ); ?>" aria-label="<?php esc_attr_e( 'Show details', 'ecf-framework' ); ?>">ⓘ</button>
                </div>
                <?php if ( $gf_heading ) : ?>
                <div class="v2-preset-fonts">
                  <span><?php echo esc_html( $gf_heading ); ?></span>
                  <?php if ( $gf_body && $gf_body !== $gf_heading ) : ?><span class="v2-preset-font-sep">+</span><span><?php echo esc_html( $gf_body ); ?></span><?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="v2-preset-desc"><?php echo esc_html( $preset['description'] ); ?></div>
                <button type="button" class="v2-btn v2-btn--primary" style="margin-top:8px;width:100%;padding:5px 8px"
                  data-v2-apply-preset="<?php echo esc_attr( $preset['slug'] ); ?>"
                  data-v2-preset-payload="<?php echo esc_attr( wp_json_encode( $preset['preset'] ) ); ?>"
                  data-v2-preset-gf-heading="<?php echo esc_attr( $gf_heading ); ?>"
                  data-v2-preset-gf-body="<?php echo esc_attr( $gf_body ); ?>"
                  data-v2-preset-title="<?php echo esc_attr( $preset['title'] ); ?>"
                  data-v2-preset-description="<?php echo esc_attr( $preset['description'] ); ?>">
                  <?php esc_html_e( 'Preset anwenden', 'ecf-framework' ); ?>
                </button>
              </div>
            </div>
            <?php endforeach; ?>

            <!-- Custom preset cards -->
            <div id="v2-custom-preset-cards">
            <?php foreach ( $custom_presets as $cp ) :
              $cp_id      = esc_attr( $cp['id'] ?? '' );
              $cp_name    = esc_html( $cp['name'] ?? 'Eigenes Preset' );
              $cp_created = esc_html( $cp['created'] ?? '' );
              $cp_payload = wp_json_encode( $cp['snapshot'] ?? [] );
            ?>
            <div class="v2-preset-card v2-preset-card--custom" data-category="eigene" data-cp-id="<?php echo $cp_id; ?>">
              <div class="v2-preset-swatch v2-preset-swatch--custom">
                <div style="font-size:var(--v2-ui-base-fs, 13px);font-weight:600;color:var(--v2-text2);letter-spacing:.03em"><?php esc_html_e( 'Eigenes Preset', 'ecf-framework' ); ?></div>
                <div style="font-size:13px;font-weight:700;color:var(--v2-text);margin-top:4px"><?php echo $cp_name; ?></div>
                <div style="font-size:var(--v2-btn-fs, 12px);color:var(--v2-text3);margin-top:4px"><?php echo $cp_created; ?></div>
              </div>
              <div class="v2-preset-body">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
                  <span class="v2-preset-tone"><?php esc_html_e( 'Eigene', 'ecf-framework' ); ?></span>
                  <span class="v2-preset-title"><?php echo $cp_name; ?></span>
                </div>
                <button type="button" class="v2-btn v2-btn--primary" style="width:100%;padding:5px 8px"
                  data-v2-apply-preset="<?php echo $cp_id; ?>"
                  data-v2-preset-payload="<?php echo esc_attr( $cp_payload ); ?>">
                  <?php esc_html_e( 'Preset anwenden', 'ecf-framework' ); ?>
                </button>
                <button type="button" class="v2-btn v2-btn--ghost" style="width:100%;padding:4px 8px;margin-top:4px;color:var(--v2-text3)"
                  data-v2-delete-custom-preset="<?php echo $cp_id; ?>">
                  <?php esc_html_e( 'Löschen', 'ecf-framework' ); ?>
                </button>
              </div>
            </div>
            <?php endforeach; ?>
            </div>

          </div>
        </div>
      </div>

      <!-- Import-Modal (selektiv) -->
      <div id="v2-import-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
        <div style="background:var(--v2-surface);border:1px solid var(--v2-border);border-radius:12px;padding:24px;width:360px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.4)">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <span style="font-size:14px;font-weight:600;color:var(--v2-text)"><?php esc_html_e( 'JSON importieren', 'ecf-framework' ); ?></span>
            <button type="button" id="v2-import-modal-close" style="background:none;border:none;color:var(--v2-text3);cursor:pointer;font-size:16px;padding:0;line-height:1">✕</button>
          </div>
          <p style="font-size:12px;color:var(--v2-text2);margin:0 0 14px"><?php esc_html_e( 'Was soll aus der Datei übernommen werden?', 'ecf-framework' ); ?></p>
          <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:20px">
            <?php foreach ( [
              ['colors',   __( 'Farben', 'ecf-framework' )],
              ['fonts',    __( 'Schriften', 'ecf-framework' )],
              ['radius',   __( 'Radius', 'ecf-framework' )],
              ['shadows',  __( 'Schatten', 'ecf-framework' )],
              ['spacing',  __( 'Abstände', 'ecf-framework' )],
              ['general',  __( 'Allgemein (Schriftgröße, Breiten…)', 'ecf-framework' )],
            ] as [$key, $label] ) : ?>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:12px;color:var(--v2-text)">
              <input type="checkbox" name="v2_import_section" value="<?php echo esc_attr( $key ); ?>" checked style="width:14px;height:14px;accent-color:var(--v2-accent)">
              <?php echo esc_html( $label ); ?>
            </label>
            <?php endforeach; ?>
          </div>
          <div style="display:flex;gap:8px">
            <button type="button" id="v2-import-modal-submit" class="v2-btn v2-btn--primary" style="flex:1"><?php esc_html_e( 'Importieren', 'ecf-framework' ); ?></button>
            <button type="button" id="v2-import-modal-cancel" class="v2-btn v2-btn--ghost"><?php esc_html_e( 'Abbrechen', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>

      <!-- Tab: Erste Schritte -->
      <div id="v2-hl-start" class="v2-tp">
        <div class="v2-sec">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:10px">
            <div style="font-size:var(--v2-ui-base-fs,13px);color:var(--v2-text2)">
              <?php esc_html_e( 'Schritt-für-Schritt von leer bis live. Status wird live aus deinen Einstellungen erkannt.', 'ecf-framework' ); ?>
            </div>
            <button type="button" class="v2-btn v2-btn--primary" data-v2-wizard-start>
              <?php esc_html_e( '🚀 Wizard starten', 'ecf-framework' ); ?>
            </button>
          </div>

          <?php
          // Status-Detection für jeden Schritt.
          $synced_class_labels = get_option( $this->synced_class_labels_option_name(), [] );
          $synced_var_labels   = get_option( $this->synced_variable_labels_option_name(), [] );
          $has_classes_synced  = is_array( $synced_class_labels ) && count( $synced_class_labels ) > 0;
          $has_vars_synced     = is_array( $synced_var_labels ) && count( $synced_var_labels ) > 0;

          $is_seeded           = ! empty( $settings['starter_classes']['seeded'] );
          $has_layrix_defaults = ! empty( $settings['layrix_class_defaults'] ) && is_array( $settings['layrix_class_defaults'] ) && count( $settings['layrix_class_defaults'] ) > 0;
          $has_auto_classes    = ! empty( $settings['auto_classes_enabled'] );

          $custom_text_color = isset( $settings['base_text_color'] ) && $settings['base_text_color'] !== '' && $settings['base_text_color'] !== '#0f172a';
          $custom_bg_color   = isset( $settings['base_background_color'] ) && $settings['base_background_color'] !== '' && $settings['base_background_color'] !== '#f8fafc';
          $has_base_setup    = $is_seeded && ( $custom_text_color || $custom_bg_color );

          $steps = [
              [
                  'num'   => '1',
                  'title' => __( 'Basiseinstellungen', 'ecf-framework' ),
                  'desc'  => __( 'Root-Größe, Schriftfamilien, Basisfarben (Text, Hintergrund, Link, Fokus) festlegen — bestimmen den Look der gesamten Site.', 'ecf-framework' ),
                  'done'  => $has_base_setup,
                  'page'  => 'settings',
                  'cta'   => __( 'Zu Einstellungen', 'ecf-framework' ),
              ],
              [
                  'num'   => '2',
                  'title' => __( 'Design-Tokens aufbauen', 'ecf-framework' ),
                  'desc'  => __( 'Farben, Abstände, Radien, Schatten und Typografie anpassen — Layrix generiert daraus die CSS-Variablen.', 'ecf-framework' ),
                  'done'  => $is_seeded,
                  'page'  => 'colors',
                  'cta'   => __( 'Zu Farben', 'ecf-framework' ),
              ],
              [
                  'num'   => '3',
                  'title' => __( 'Klassen-Defaults konfigurieren', 'ecf-framework' ),
                  'desc'  => __( 'Settings → Plugin → Klassen-Defaults: Layrix-Werte für Button, Heading, Section, Container feinabstimmen — bestimmt was die Layrix-Klassen automatisch tun.', 'ecf-framework' ),
                  'done'  => $has_layrix_defaults,
                  'page'  => 'settings',
                  'cta'   => __( 'Zu Klassen-Defaults', 'ecf-framework' ),
              ],
              [
                  'num'   => '4',
                  'title' => __( 'Auto-Klassen aktivieren', 'ecf-framework' ),
                  'desc'  => __( 'Settings → Plugin → Allgemein: Toggle „Auto-Klassen aktiviert" einschalten — Headings (h1–h5), Buttons und Text-Links bekommen automatisch ihre Layrix-Klasse, sobald sie in Elementor eingefügt werden.', 'ecf-framework' ),
                  'done'  => $has_auto_classes,
                  'page'  => 'settings',
                  'cta'   => __( 'Zu Auto-Klassen', 'ecf-framework' ),
              ],
              [
                  'num'   => '5',
                  'title' => __( 'Sync ausführen', 'ecf-framework' ),
                  'desc'  => __( 'Sync & Export ausführen — Variablen und Klassen landen in Elementor\'s Global-Registry. Offene Editor-Tabs danach neu laden.', 'ecf-framework' ),
                  'done'  => $has_classes_synced && $has_vars_synced,
                  'page'  => 'sync',
                  'cta'   => __( 'Zu Sync & Export', 'ecf-framework' ),
              ],
              [
                  'num'   => '6',
                  'title' => __( 'In Elementor bauen', 'ecf-framework' ),
                  'desc'  => __( 'Layrix Section aus dem Widget-Panel ziehen, Headings und Buttons reinwerfen — Padding, Schriftgröße, Border-Radius greifen automatisch aus deinen Token-Defaults. Anwendung-Tab in Layrix für Klassen-Übersicht.', 'ecf-framework' ),
                  'done'  => false,
                  'page'  => 'cookbook',
                  'cta'   => __( 'Zur Anwendung', 'ecf-framework' ),
              ],
          ];

          $done_count = 0;
          foreach ( $steps as $s ) {
              if ( ! empty( $s['done'] ) ) $done_count++;
          }
          $total = count( $steps );
          $pct   = $total > 0 ? (int) round( $done_count / $total * 100 ) : 0;
          ?>

          <!-- Progress bar -->
          <div style="margin-bottom:18px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
              <span style="font-size:var(--v2-ui-base-fs,13px);color:var(--v2-text2)"><?php
                  /* translators: %1$d: completed steps, %2$d: total steps */
                  printf( esc_html__( 'Fortschritt: %1$d / %2$d Schritte', 'ecf-framework' ), $done_count, $total );
              ?></span>
              <span style="font-size:var(--v2-btn-fs,12px);color:var(--v2-accent2,#a5b4fc);font-weight:600"><?php echo esc_html( $pct ); ?>%</span>
            </div>
            <div style="height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden">
              <div style="height:100%;width:<?php echo esc_attr( $pct ); ?>%;background:linear-gradient(90deg,var(--v2-accent),var(--v2-accent2,#a5b4fc));transition:width .4s ease"></div>
            </div>
          </div>

          <?php foreach ( $steps as $s ) :
              $is_done = ! empty( $s['done'] );
          ?>
          <div class="v2-hc v2-hc--step<?php echo $is_done ? ' is-done' : ''; ?>" style="display:flex;gap:14px;align-items:flex-start;padding:14px;border:1px solid var(--v2-border);border-radius:8px;margin-bottom:10px;background:<?php echo $is_done ? 'rgba(16,185,129,.04)' : 'rgba(255,255,255,.02)'; ?>">
            <div style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:<?php echo $is_done ? '#10b981' : 'rgba(99,102,241,.2)'; ?>;color:<?php echo $is_done ? '#fff' : 'var(--v2-accent2,#a5b4fc)'; ?>;font-weight:700;font-size:var(--v2-ui-base-fs,13px)">
              <?php if ( $is_done ) : ?>
                <svg width="14" height="14" viewBox="0 0 13 13" fill="none" aria-hidden="true"><path d="M2.5 6.5L5 9l5.5-5.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <?php else : ?>
                <?php echo esc_html( $s['num'] ); ?>
              <?php endif; ?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                <strong style="font-size:var(--v2-ui-base-fs,13px);color:var(--v2-text1)"><?php echo esc_html( $s['title'] ); ?></strong>
                <?php if ( $is_done ) : ?>
                  <span style="font-size:var(--v2-ui-base-fs, 13px);color:#10b981;font-weight:600;letter-spacing:.04em;text-transform:uppercase"><?php esc_html_e( '✓ Erledigt', 'ecf-framework' ); ?></span>
                <?php else : ?>
                  <span style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3);font-weight:500;letter-spacing:.04em;text-transform:uppercase"><?php esc_html_e( '⏳ Offen', 'ecf-framework' ); ?></span>
                <?php endif; ?>
              </div>
              <div style="font-size:var(--v2-ui-base-fs,13px);color:var(--v2-text3);line-height:1.5;margin-bottom:8px"><?php echo esc_html( $s['desc'] ); ?></div>
              <button type="button" class="v2-btn v2-btn--ghost" onclick="ecfV2Go('<?php echo esc_js( $s['page'] ); ?>')" style="padding:5px 12px"><?php echo esc_html( $s['cta'] ); ?> →</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Tab: Design-Gesundheit -->
      <div id="v2-hl-health" class="v2-tp">
        <div class="v2-sec">
          <?php foreach ( $design_health['checks'] ?? [] as $dhc ) :
            $dh_status = $dhc['status'] ?? 'notice';
          ?>
          <div class="v2-health-item v2-health-item--<?php echo esc_attr( $dh_status ); ?>">
            <div class="v2-health-dot"></div>
            <div style="flex:1;min-width:0">
              <div class="v2-health-title"><?php echo esc_html( $dhc['title'] ); ?></div>
              <div class="v2-health-msg"><?php echo esc_html( $dhc['message'] ); ?></div>
            </div>
            <div class="v2-health-val"><?php echo esc_html( $dhc['value'] ?? '' ); ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Tab: Smart Empfehlungen -->
      <div id="v2-hl-recs" class="v2-tp">
        <div class="v2-sec">
          <div class="v2-rec-list">
          <?php foreach ( $smart_recs as $rec ) :
            $is_healthy = empty( $rec['payload'] );
          ?>
          <div class="v2-rec-card v2-rec-card--<?php echo esc_attr( $is_healthy ? 'healthy' : 'action' ); ?>">
            <div class="v2-rec-tone"><?php echo esc_html( $rec['tone'] ); ?></div>
            <div class="v2-rec-title"><?php echo esc_html( $rec['title'] ); ?></div>
            <div class="v2-rec-desc"><?php echo esc_html( $rec['description'] ); ?></div>
            <div class="v2-rec-impact"><?php echo esc_html( $rec['impact'] ); ?></div>
            <?php if ( ! empty( $rec['apply_label'] ) && ! empty( $rec['payload'] ) ) : ?>
            <button type="button" class="v2-btn v2-btn--ghost v2-btn--sm"
                    data-v2-apply-rec-payload="<?php echo esc_attr( wp_json_encode( $rec['payload'] ) ); ?>">
              <?php echo esc_html( $rec['apply_label'] ); ?>
            </button>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <aside class="v2-aside">
      <div class="v2-as-block">
        <div class="v2-as-head"><?php esc_html_e( 'Empfohlene Reihenfolge', 'ecf-framework' ); ?></div>
        <?php foreach ( [
          [ '1', __( 'Basiseinstellungen', 'ecf-framework' ), 'settings' ],
          [ '2', __( 'Farben & Radius', 'ecf-framework' ), 'colors' ],
          [ '3', __( 'Typografie', 'ecf-framework' ), 'typography' ],
          [ '4', __( 'Abstände & Schatten', 'ecf-framework' ), 'spacing' ],
          [ '5', __( 'Klassen wählen', 'ecf-framework' ), 'classes' ],
          [ '6', __( 'Sync & Export', 'ecf-framework' ), 'sync' ],
        ] as [$num, $label, $page] ) : ?>
        <div class="v2-as-row" style="cursor:pointer;gap:8px" onclick="ecfV2Go('<?php echo esc_js( $page ); ?>')">
          <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:rgba(255,255,255,.07);font-size:var(--v2-btn-fs, 12px);font-weight:700;flex-shrink:0"><?php echo esc_html( $num ); ?></span>
          <span class="v2-as-k"><?php echo esc_html( $label ); ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </aside>
  </div>
</div><!-- /erste-schritte page -->

<!-- ═══ PAGE: EINSTELLUNGEN ══════════════════════════════════════════ -->
<div id="ecf-v2-page-settings" class="v2-page">
  <div class="v2-topbar">
    <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e( 'Einstellungen', 'ecf-framework' ); ?></span></div>
    <div class="v2-topbar-r">
      <div class="v2-actions-menu">
        <button type="button" class="v2-btn v2-btn--primary v2-actions-toggle" data-v2-actions-toggle aria-haspopup="menu" aria-expanded="false" title="<?php esc_attr_e( 'Weitere Aktionen', 'ecf-framework' ); ?>"><span class="v2-actions-toggle__label"><?php esc_html_e( 'Aktionen', 'ecf-framework' ); ?></span><span class="v2-actions-toggle__chevron" aria-hidden="true">▾</span></button>
        <div class="v2-actions-menu__dropdown" role="menu" hidden>
          <button type="button" role="menuitem" class="v2-actions-menu__item v2-actions-menu__item--danger" data-v2-reset-defaults title="<?php esc_attr_e( 'Reset ALL settings to plugin defaults', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">↺</span><span><?php esc_html_e( 'Auf Grundeinstellungen zurücksetzen', 'ecf-framework' ); ?></span></button>
          <button type="button" role="menuitem" class="v2-actions-menu__item" data-v2-reset title="<?php esc_attr_e( 'Discard unsaved changes and reload page', 'ecf-framework' ); ?>"><span class="v2-actions-menu__icon" aria-hidden="true">✕</span><span><?php esc_html_e( 'Änderungen verwerfen', 'ecf-framework' ); ?></span></button>
        </div>
      </div>
      <button type="button" class="v2-btn v2-btn--outline" data-v2-save><?php esc_html_e( 'Speichern', 'ecf-framework' ); ?></button>
    </div>
  </div>
  <div class="v2-page-body">
    <div class="v2-content">
      <div class="v2-ph"><h1><?php esc_html_e( 'Einstellungen', 'ecf-framework' ); ?></h1><p><?php esc_html_e( 'Globale Basiswerte für das Design-System — beeinflussen alle Tokens und das generierte CSS.', 'ecf-framework' ); ?></p></div>

      <!-- Tabs -->
      <div class="v2-tabs v2-tabs--icon" style="margin-bottom:20px">
        <button type="button" class="v2-tab v2-tab--on" onclick="ecfV2Tab('st','design',this)" data-v2-tab-group="st" data-v2-tab="design"><span class="v2-tab-icon">⌂</span><?php esc_html_e( 'Webseite', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" onclick="ecfV2Tab('st','system',this)" data-v2-tab-group="st" data-v2-tab="system"><span class="v2-tab-icon">⚙</span><?php esc_html_e( 'Plugin', 'ecf-framework' ); ?></button>
      </div>

      <?php
      // Helper: Render small token info (CSS variable / class name) under a setting row.
      // Reuses $token_label defined earlier in the cookbook block (same function scope).
      $tok = function ( $vars = [], $classes = [] ) use ( $token_label ) {
          $vars = array_filter( (array) $vars );
          $classes = array_filter( (array) $classes );
          if ( ! $vars && ! $classes ) {
              return;
          }
          ?>
          <div class="v2-tok">
              <?php if ( $vars ) : ?>
                  <span class="v2-tok-l"><?php esc_html_e( 'VAR', 'ecf-framework' ); ?></span>
                  <?php foreach ( $vars as $v ) :
                      $label = $token_label( $v );
                  ?><code<?php if ( $label ) : ?> data-v2-tip="<?php echo esc_attr( $label ); ?>"<?php endif; ?>><?php echo esc_html( $v ); ?></code><?php endforeach; ?>
              <?php endif; ?>
              <?php if ( $classes ) : ?>
                  <span class="v2-tok-l"><?php esc_html_e( 'CLASS', 'ecf-framework' ); ?></span>
                  <?php foreach ( $classes as $c ) : ?><code data-v2-tip="<?php esc_attr_e( 'CSS-Klasse', 'ecf-framework' ); ?>"><?php echo esc_html( $c ); ?></code><?php endforeach; ?>
              <?php endif; ?>
          </div>
          <?php
      };
      ?>

      <!-- TAB: DESIGN -->
      <div id="v2-st-design" class="v2-tp v2-tp--on">

      <!-- Sub-tabs: Typografie / Farben / Layout -->
      <div class="v2-tabs v2-tabs--sub">
        <button type="button" class="v2-tab v2-tab--on" data-v2-tab-group="wb" data-v2-tab="typo"><?php esc_html_e( 'Typografie', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="wb" data-v2-tab="colors"><?php esc_html_e( 'Farben', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="wb" data-v2-tab="layout"><?php esc_html_e( 'Layout', 'ecf-framework' ); ?></button>
      </div>

      <!-- SUB-TAB: Typografie -->
      <div id="v2-wb-typo" class="v2-tp v2-tp--on">

      <!-- Schriften -->
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Schriften', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <?php
          $fi_primary   = null;
          $fi_secondary = null;
          foreach ( $fonts_arr as $fi => $frow ) {
              if ( ( $frow['name'] ?? '' ) === 'primary' )   $fi_primary   = $fi;
              if ( ( $frow['name'] ?? '' ) === 'secondary' ) $fi_secondary = $fi;
          }
          if ( $fi_primary !== null ) :
          ?>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Fließtext', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Schrift für Absätze und Fließtext', 'ecf-framework' ); ?></div>
              <?php $tok( [ '--ecf-base-font-family', '--ecf-base-body-font-family' ] ); ?>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[typography][fonts][<?php echo $fi_primary; ?>][name]" value="primary">
              <input type="text" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[typography][fonts][<?php echo $fi_primary; ?>][value]" value="<?php echo esc_attr( $font_primary ); ?>" style="max-width:220px">
              <button type="button" class="v2-btn v2-btn--ghost" style="padding:2px 8px" onclick="document.querySelector('[data-v2-page=typography]').click()">→ <?php esc_html_e( 'Schriften', 'ecf-framework' ); ?></button>
            </div>
          </div>
          <?php endif; ?>
          <?php if ( $fi_secondary !== null ) : ?>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Überschriften', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Schrift für H1–H6', 'ecf-framework' ); ?></div>
              <?php $tok( [ '--ecf-heading-font-family' ] ); ?>
            </div>
            <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[typography][fonts][<?php echo $fi_secondary; ?>][name]" value="secondary">
            <input type="text" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[typography][fonts][<?php echo $fi_secondary; ?>][value]" value="<?php echo esc_attr( $font_secondary ); ?>" style="max-width:220px">
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Typography Base -->
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Typografie-Basis', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <div class="v2-sr">
            <div><div class="v2-sl"><?php esc_html_e( 'Root Font Size', 'ecf-framework' ); ?></div><div class="v2-sh2"><?php esc_html_e( '62.5% = 10px base, 100% = 16px base', 'ecf-framework' ); ?></div></div>
            <div style="display:flex;align-items:center;gap:6px">
              <select class="v2-si" id="v2-root-font-sel" name="<?php echo esc_attr( $opt ); ?>[root_font_size]" style="max-width:120px">
                <option value="62.5" <?php selected( $root_size, '62.5' ); ?>>62.5% (10px)</option>
                <option value="100"  <?php selected( $root_size, '100' ); ?>>100% (16px)</option>
              </select>
              <button type="button" id="v2-rfi-btn" title="<?php esc_attr_e( 'rem → px Referenz', 'ecf-framework' ); ?>" style="background:none;border:1px solid var(--v2-border);border-radius:50%;width:22px;height:22px;font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3);cursor:pointer;flex-shrink:0;line-height:1">?</button>
            </div>
          </div>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Body Text Size', 'ecf-framework' ); ?></div>
              <?php $tok( [ '--ecf-base-body-text-size' ] ); ?>
            </div>
            <?php
              $btss = $settings['base_body_text_size'] ?? '16px';
              preg_match( '/^\s*([\d.\-]+)\s*([a-z%]+)?\s*$/i', (string) $btss, $bm );
              $bts_num  = $bm[1] ?? '16';
              $bts_unit = $bm[2] ?? 'px';
              $rem_base = ( $root_size === '62.5' ) ? 10 : 16;
            ?>
            <div class="v2-unit-input" data-rem-base="<?php echo esc_attr( (string) $rem_base ); ?>">
              <input type="number" class="v2-si v2-unit-num" value="<?php echo esc_attr( $bts_num ); ?>" step="any" style="max-width:70px">
              <select class="v2-si v2-unit-sel" style="max-width:64px">
                <?php foreach ( [ 'px', 'rem', 'em', '%', 'vw' ] as $u ) : ?>
                  <option value="<?php echo esc_attr( $u ); ?>" <?php selected( $bts_unit, $u ); ?>><?php echo esc_html( $u ); ?></option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[base_body_text_size]" class="v2-unit-hidden" value="<?php echo esc_attr( $btss ); ?>">
            </div>
          </div>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Body Font Weight', 'ecf-framework' ); ?></div>
              <?php $tok( [ '--ecf-base-body-font-weight' ] ); ?>
            </div>
            <select class="v2-si" name="<?php echo esc_attr( $opt ); ?>[base_body_font_weight]" style="max-width:110px">
              <?php foreach ( [ '300' => 'Light 300', '400' => 'Normal 400', '500' => 'Medium 500', '600' => 'SemiBold 600', '700' => 'Bold 700' ] as $wv => $wl ) : ?>
              <option value="<?php echo esc_attr( $wv ); ?>" <?php selected( $settings['base_body_font_weight'] ?? '400', $wv ); ?>><?php echo esc_html( $wl ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      </div><!-- /v2-wb-typo -->

      <!-- SUB-TAB: Farben -->
      <div id="v2-wb-colors" class="v2-tp">

      <!-- Hauptfarben -->
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Hauptfarben', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <?php if ( isset( $c_idx['primary'] ) ) : ?>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Primärfarbe', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Hauptfarbe für Buttons und Akzente', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo (int) $c_idx['primary']; ?>][name]" value="primary">
              <input type="color" class="v2-si v2-si--color" name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo (int) $c_idx['primary']; ?>][value]" value="<?php echo esc_attr( $c['primary'] ?? '#6366f1' ); ?>">
              <button type="button" class="v2-btn v2-btn--ghost" style="padding:2px 8px" onclick="document.querySelector('[data-v2-page=colors]').click()">→ <?php esc_html_e( 'Farben', 'ecf-framework' ); ?></button>
            </div>
          </div>
          <?php endif; ?>
          <?php if ( isset( $c_idx['accent'] ) ) : ?>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Akzentfarbe', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Zweite Farbe für Highlights und Links', 'ecf-framework' ); ?></div>
            </div>
            <input type="hidden" name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo (int) $c_idx['accent']; ?>][name]" value="accent">
            <input type="color" class="v2-si v2-si--color" name="<?php echo esc_attr( $opt ); ?>[colors][<?php echo (int) $c_idx['accent']; ?>][value]" value="<?php echo esc_attr( $c['accent'] ?? '#0ea5e9' ); ?>">
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Base Colors moved to Farben-Page (Sidebar → Farben) -->
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Basisfarben', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <div class="v2-sr" style="align-items:center">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Body text, body background, link and focus colors', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'These settings have moved to the Colors page. Click on the right to jump there.', 'ecf-framework' ); ?></div>
            </div>
            <button type="button" class="v2-btn v2-btn--ghost" onclick="document.querySelector('[data-v2-page=colors]').click()">→ <?php esc_html_e( 'Farben', 'ecf-framework' ); ?></button>
          </div>
        </div>
      </div>

      </div><!-- /v2-wb-colors -->

      <!-- SUB-TAB: Layout -->
      <div id="v2-wb-layout" class="v2-tp">

      <!-- Abstände -->
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Abstände', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Basisabstand (min)', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Kleinster Abstand in px — alle anderen Abstände leiten sich daraus ab', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              <input type="number" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[spacing][min_base]" value="<?php echo esc_attr( $sp_base_min ); ?>" style="max-width:80px" min="8" max="40">
              <button type="button" class="v2-btn v2-btn--ghost" style="padding:2px 8px" onclick="document.querySelector('[data-v2-page=spacing]').click()">→ <?php esc_html_e( 'Abstände', 'ecf-framework' ); ?></button>
            </div>
          </div>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Basisabstand (max)', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Größter Abstand in px (bei breiten Bildschirmen)', 'ecf-framework' ); ?></div>
            </div>
            <input type="number" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[spacing][max_base]" value="<?php echo esc_attr( $sp_base_max ); ?>" style="max-width:80px" min="8" max="60">
          </div>
        </div>
      </div>

      <!-- Layout & Container -->
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Layout & Container', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Container Width', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Max width for Elementor boxed layout', 'ecf-framework' ); ?></div>
              <?php $tok( [ '--ecf-container-boxed' ], [ '.ecf-container-boxed' ] ); ?>
            </div>
            <input type="text" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[elementor_boxed_width]" value="<?php echo esc_attr( $container_w ); ?>" style="max-width:110px">
          </div>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Max Text Width', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Optimal reading width for body text', 'ecf-framework' ); ?></div>
              <?php $tok( [ '--ecf-content-max-width' ], [ '.ecf-content-width' ] ); ?>
            </div>
            <input type="text" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[content_max_width]" value="<?php echo esc_attr( $text_max_w ); ?>" style="max-width:110px">
          </div>
        </div>
      </div>

      <!-- Root Font Impact -->
      <?php
        $rfi = $this->root_font_impact_preview_data( $settings );
        $rfi_base_px   = $rfi['root_base_px'] ?? 16;
        $rfi_type      = $rfi['type'] ?? [];
        $rfi_spacing   = $rfi['spacing'] ?? [];
        $rfi_radius    = $rfi['radius'] ?? [];
        $rfi_type_step = $rfi_type['step'] ?? 'm';
        $rfi_sp_step   = $rfi_spacing['step'] ?? 'm';
        $rfi_r_name    = sanitize_key( $rfi_radius['name'] ?? 'm' );
      ?>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Basisgröße & Skalierung', 'ecf-framework' ); ?></div>
        <p style="font-size:11.5px;color:var(--v2-text3);margin:0 0 10px;line-height:1.5"><?php printf( esc_html__( 'Aktuell %spx als Basis. Wenn du diese Zahl änderst, passen sich Schriftgrößen, Abstände und Ecken auf der gesamten Website automatisch an.', 'ecf-framework' ), esc_html( $rfi_base_px ) ); ?></p>
        <div class="v2-sg v2-sg--compact2">
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><code style="font-family:var(--v2-mono);font-size:var(--v2-btn-fs, 12px)">--ecf-text-<?php echo esc_html( $rfi_type_step ); ?></code></div>
              <div class="v2-sh2"><?php esc_html_e( 'Schriftgröße (Fließtext)', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
              <div style="display:flex;gap:10px;font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text2)">
                <span><?php echo esc_html( ( $rfi_type['min_px'] ?? $rfi_type['minPx'] ?? '?' ) . 'px' ); ?> <span style="color:var(--v2-text3);font-size:var(--v2-btn-fs, 12px)">min</span></span>
                <span><?php echo esc_html( ( $rfi_type['max_px'] ?? $rfi_type['maxPx'] ?? '?' ) . 'px' ); ?> <span style="color:var(--v2-text3);font-size:var(--v2-btn-fs, 12px)">max</span></span>
              </div>
              <button type="button" class="v2-btn v2-btn--ghost" style="padding:2px 8px" onclick="document.querySelector('[data-v2-page=typography]').click()" title="<?php esc_attr_e( 'Zur Typografie-Seite', 'ecf-framework' ); ?>">→ <?php esc_html_e( 'Typografie', 'ecf-framework' ); ?></button>
            </div>
          </div>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><code style="font-family:var(--v2-mono);font-size:var(--v2-btn-fs, 12px)">--ecf-space-<?php echo esc_html( $rfi_sp_step ); ?></code></div>
              <div class="v2-sh2"><?php esc_html_e( 'Abstand (Basis)', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
              <div style="display:flex;gap:10px;font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text2)">
                <span><?php echo esc_html( ( $rfi_spacing['min_px'] ?? $rfi_spacing['minPx'] ?? '?' ) . 'px' ); ?> <span style="color:var(--v2-text3);font-size:var(--v2-btn-fs, 12px)">min</span></span>
                <span><?php echo esc_html( ( $rfi_spacing['max_px'] ?? $rfi_spacing['maxPx'] ?? '?' ) . 'px' ); ?> <span style="color:var(--v2-text3);font-size:var(--v2-btn-fs, 12px)">max</span></span>
              </div>
              <button type="button" class="v2-btn v2-btn--ghost" style="padding:2px 8px" onclick="document.querySelector('[data-v2-page=spacing]').click()" title="<?php esc_attr_e( 'Zur Abstände-Seite', 'ecf-framework' ); ?>">→ <?php esc_html_e( 'Abstände', 'ecf-framework' ); ?></button>
            </div>
          </div>
          <?php if ( ! empty( $rfi_r_name ) ) : ?>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><code style="font-family:var(--v2-mono);font-size:var(--v2-btn-fs, 12px)">--ecf-radius-<?php echo esc_html( $rfi_r_name ); ?></code></div>
              <div class="v2-sh2"><?php esc_html_e( 'Eckenrundung (Basis)', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
              <div style="display:flex;gap:10px;font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text2)">
                <span><?php echo esc_html( $rfi_radius['min'] ?? '?' ); ?> <span style="color:var(--v2-text3);font-size:var(--v2-btn-fs, 12px)">min</span></span>
                <span><?php echo esc_html( $rfi_radius['max'] ?? $rfi_radius['min'] ?? '?' ); ?> <span style="color:var(--v2-text3);font-size:var(--v2-btn-fs, 12px)">max</span></span>
              </div>
              <button type="button" class="v2-btn v2-btn--ghost" style="padding:2px 8px" onclick="document.querySelector('[data-v2-page=radius]').click()" title="<?php esc_attr_e( 'Zur Radius-Seite', 'ecf-framework' ); ?>">→ <?php esc_html_e( 'Radius', 'ecf-framework' ); ?></button>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      </div><!-- /v2-wb-layout -->

      </div><!-- /v2-st-design -->

      <!-- TAB: SYSTEM -->
      <div id="v2-st-system" class="v2-tp">

      <!-- Plugin sub-tabs -->
      <div class="v2-tabs v2-tabs--sub">
        <button type="button" class="v2-tab v2-tab--on" data-v2-tab-group="pl" data-v2-tab="general"><?php esc_html_e( 'Allgemein', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="pl" data-v2-tab="classes"><?php esc_html_e( 'Klassen-Defaults', 'ecf-framework' ); ?></button>
        <button type="button" class="v2-tab" data-v2-tab-group="pl" data-v2-tab="fonts"><?php esc_html_e( 'Schriften', 'ecf-framework' ); ?></button>
      </div>

      <!-- SUB-TAB: Allgemein -->
      <div id="v2-pl-general" class="v2-tp v2-tp--on">

      <!-- Interface -->
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Allgemein', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <div class="v2-sr">
            <div><div class="v2-sl"><?php esc_html_e( 'Sprache', 'ecf-framework' ); ?></div></div>
            <select class="v2-si" name="<?php echo esc_attr( $opt ); ?>[interface_language]" style="max-width:120px">
              <option value="de" <?php selected( $settings['interface_language'] ?? 'de', 'de' ); ?>>Deutsch</option>
              <option value="en" <?php selected( $settings['interface_language'] ?? 'de', 'en' ); ?>>English</option>
            </select>
          </div>
          <div class="v2-sr">
            <div><div class="v2-sl"><?php esc_html_e( 'Autosave', 'ecf-framework' ); ?></div><div class="v2-sh2"><?php esc_html_e( 'Änderungen automatisch speichern', 'ecf-framework' ); ?></div></div>
            <label class="v2-tog-label">
              <input type="checkbox" class="v2-tog-cb" name="<?php echo esc_attr( $opt ); ?>[autosave_enabled]" value="1" <?php checked( ! empty( $settings['autosave_enabled'] ) ); ?>>
              <span class="v2-tog<?php echo ! empty( $settings['autosave_enabled'] ) ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
            </label>
          </div>
        </div>
      </div>

      </div><!-- /v2-pl-general -->

      <!-- SUB-TAB: Schriften -->
      <!-- SUB-TAB: Klassen-Defaults -->
      <div id="v2-pl-classes" class="v2-tp">
        <div class="v2-sec">
          <div class="v2-sh"><?php esc_html_e( 'Klassen-Defaults', 'ecf-framework' ); ?></div>
          <div class="v2-sh2" style="margin-bottom:14px"><?php esc_html_e( 'Bestimme welche Variablen Layrix in seinen Standard-Klassen verwendet. Änderungen werden beim Speichern in Elementor\'s Global-Classes-Registry geschrieben.', 'ecf-framework' ); ?></div>

          <?php
          $cls_schema = method_exists( $this, 'layrix_class_defaults_schema' ) ? $this->layrix_class_defaults_schema() : [];
          $cls_options = method_exists( $this, 'layrix_size_variable_options' ) ? $this->layrix_size_variable_options() : [];
          $cls_defaults = $settings['layrix_class_defaults'] ?? [];

          $render_cls_select = function ( $class_name, $prop_key, $current_value, $schema_default ) use ( $opt, $cls_options ) {
              $name_attr = esc_attr( $opt ) . '[layrix_class_defaults][' . esc_attr( $class_name ) . '][' . esc_attr( $prop_key ) . ']';
              $value     = $current_value !== '' ? $current_value : $schema_default;
              ?>
              <select class="v2-si" name="<?php echo $name_attr; ?>" style="max-width:240px">
                  <option value=""><?php
                      printf(
                          /* translators: %s: variable name */
                          esc_html__( 'Default (%s)', 'ecf-framework' ),
                          esc_html( $schema_default )
                      );
                  ?></option>
                  <?php foreach ( $cls_options as $group_label => $vars ) : ?>
                      <optgroup label="<?php echo esc_attr( $group_label ); ?>">
                          <?php foreach ( $vars as $var ) : ?>
                              <option value="<?php echo esc_attr( $var ); ?>" <?php selected( $value, $var ); ?>><?php echo esc_html( $var ); ?></option>
                          <?php endforeach; ?>
                      </optgroup>
                  <?php endforeach; ?>
              </select>
              <?php
          };
          ?>

          <?php
            // Group schema entries by category for accordion rendering.
            $cls_by_cat = [];
            foreach ( $cls_schema as $class_name => $cls_def ) {
              $cat = sanitize_key( $cls_def['category'] ?? 'other' );
              $cls_by_cat[ $cat ][ $class_name ] = $cls_def;
            }
            $cat_labels = [
              'typography' => __( 'Überschriften',     'ecf-framework' ),
              'components' => __( 'Komponenten',        'ecf-framework' ),
              'sections'   => __( 'Sektionen',          'ecf-framework' ),
              'layout'     => __( 'Layout',             'ecf-framework' ),
              'other'      => __( 'Weitere Klassen',    'ecf-framework' ),
            ];
            // Stable order: typography, components, sections, layout, then any remaining
            $cat_order = [ 'typography', 'components', 'sections', 'layout' ];
            foreach ( array_keys( $cls_by_cat ) as $k ) {
              if ( ! in_array( $k, $cat_order, true ) ) $cat_order[] = $k;
            }
          ?>

          <?php $cat_first_rendered = false; foreach ( $cat_order as $cat ) :
            if ( empty( $cls_by_cat[ $cat ] ) ) continue;
            $cat_label = $cat_labels[ $cat ] ?? ucfirst( $cat );
            $cat_count = count( $cls_by_cat[ $cat ] );
            $cat_open = ! $cat_first_rendered; // first non-empty category open, rest collapsed
            $cat_first_rendered = true;
          ?>
            <details class="v2-cls-cat"<?php echo $cat_open ? ' open' : ''; ?> style="margin-bottom:12px;border:1px solid var(--v2-border);border-radius:8px;background:rgba(255,255,255,.02);overflow:hidden">
              <summary style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;cursor:pointer;user-select:none;list-style:none;font-weight:600;font-size:13.5px">
                <span style="display:flex;align-items:center;gap:10px">
                  <span class="v2-cls-cat__chevron" style="display:inline-block;font-size:10px;opacity:.6;transition:transform .15s">▼</span>
                  <span><?php echo esc_html( $cat_label ); ?></span>
                  <span style="font-weight:400;font-size:11.5px;color:var(--v2-text3)">(<?php echo (int) $cat_count; ?>)</span>
                </span>
              </summary>
              <div style="padding:0 14px 14px">
                <?php foreach ( $cls_by_cat[ $cat ] as $class_name => $cls_def ) : ?>
                  <div class="v2-sec" style="margin:10px 0 0;border:1px solid var(--v2-border);border-radius:8px;padding:12px 14px;background:rgba(0,0,0,.18)">
                    <div class="v2-sh" style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                      <span><?php echo esc_html( $cls_def['label'] ); ?></span>
                      <code style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:var(--v2-btn-fs,12px);background:rgba(0,0,0,.3);padding:2px 8px;border-radius:4px;color:var(--v2-text2);font-weight:400">.<?php echo esc_html( $class_name ); ?></code>
                    </div>
                    <div class="v2-sg">
                      <?php foreach ( $cls_def['props'] as $prop_key => $prop_def ) :
                        $current = $cls_defaults[ $class_name ][ $prop_key ] ?? '';
                        $sch_def = $prop_def['default'] ?? '';
                      ?>
                      <div class="v2-sr">
                        <div>
                          <div class="v2-sl"><?php echo esc_html( $prop_def['label'] ); ?></div>
                          <div class="v2-sh2"><code style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:var(--v2-btn-fs,12px);color:var(--v2-text3)"><?php echo esc_html( $prop_key ); ?></code></div>
                        </div>
                        <?php $render_cls_select( $class_name, $prop_key, $current, $sch_def ); ?>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </details>
          <?php endforeach; ?>

          <style>
            .v2-cls-cat > summary::-webkit-details-marker { display: none; }
            .v2-cls-cat[open] .v2-cls-cat__chevron { transform: rotate(0deg); }
            .v2-cls-cat:not([open]) .v2-cls-cat__chevron { transform: rotate(-90deg); }
            .v2-cls-cat > summary:hover { background: rgba(255,255,255,.03); }
          </style>
        </div>
      </div>

      <?php
        $v2_base_fs = (int) ( $settings['ui_base_font_size'] ?? 13 );
        $v2_nav_fs  = (int) ( $settings['ui_nav_font_size']  ?? 13 );
        $v2_font    = $settings['ui_font_family'] ?? '';
      ?>
      <div id="v2-pl-fonts" class="v2-tp">
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Schriften Plugin-Oberfläche', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Schriftfamilie', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Aktiv:', 'ecf-framework' ); ?> <span id="v2-ui-font-hint"><?php echo esc_html( $v2_font ?: 'Plus Jakarta Sans' ); ?></span></div>
            </div>
            <input type="text" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[ui_font_family]" value="<?php echo esc_attr( $v2_font ); ?>" placeholder="Plus Jakarta Sans, system-ui, sans-serif" style="max-width:240px">
          </div>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Fließtext', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Beschreibungen und Hilfetexte', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:6px">
              <input type="number" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[ui_base_font_size]" value="<?php echo esc_attr( $v2_base_fs ); ?>" style="max-width:70px" min="10" max="18">
              <span style="color:var(--v2-text3)">px</span>
            </div>
          </div>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Navigation', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Sidebar-Links und Menüpunkte', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:6px">
              <input type="number" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[ui_nav_font_size]" value="<?php echo esc_attr( $v2_nav_fs ); ?>" style="max-width:70px" min="10" max="18">
              <span style="color:var(--v2-text3)">px</span>
            </div>
          </div>
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Buttons', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Alle Buttons und interaktive Elemente', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:6px">
              <input type="number" class="v2-si" name="<?php echo esc_attr( $opt ); ?>[ui_btn_font_size]" value="<?php echo esc_attr( $v2_btn_fs ); ?>" style="max-width:70px" min="10" max="18">
              <span style="color:var(--v2-text3)">px</span>
            </div>
          </div>
        </div>
      </div>
      </div><!-- /v2-pl-fonts -->

      <!-- Elementor Sync -->
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Elementor Sync', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <!-- Master Toggle -->
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Auto-Sync aktiviert', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Nach jedem Speichern automatisch zu Elementor synchronisieren', 'ecf-framework' ); ?></div>
            </div>
            <label class="v2-tog-label">
              <input type="checkbox" class="v2-tog-cb" name="<?php echo esc_attr( $opt ); ?>[elementor_auto_sync_enabled]" value="1" <?php checked( ! empty( $settings['elementor_auto_sync_enabled'] ) ); ?>>
              <span class="v2-tog<?php echo ! empty( $settings['elementor_auto_sync_enabled'] ) ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
            </label>
          </div>
          <!-- Erweitert -->
          <details class="v2-advanced-toggle">
            <summary><?php esc_html_e( 'Erweiterte Sync-Optionen', 'ecf-framework' ); ?></summary>
            <div class="v2-advanced-body">
              <div class="v2-sr">
                <div>
                  <div class="v2-sl"><?php esc_html_e( 'Variablen synchronisieren', 'ecf-framework' ); ?></div>
                  <div class="v2-sh2"><?php esc_html_e( 'Farben, Abstände, Radius, Typografie und Schatten', 'ecf-framework' ); ?></div>
                </div>
                <label class="v2-tog-label">
                  <input type="checkbox" class="v2-tog-cb" name="<?php echo esc_attr( $opt ); ?>[elementor_auto_sync_variables]" value="1" <?php checked( ! empty( $settings['elementor_auto_sync_variables'] ) ); ?>>
                  <span class="v2-tog<?php echo ! empty( $settings['elementor_auto_sync_variables'] ) ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
                </label>
              </div>
              <div class="v2-sr">
                <div>
                  <div class="v2-sl"><?php esc_html_e( 'Klassen synchronisieren', 'ecf-framework' ); ?></div>
                  <div class="v2-sh2"><?php esc_html_e( 'Starter- und Utility-Klassen zu Elementor übertragen', 'ecf-framework' ); ?></div>
                </div>
                <label class="v2-tog-label">
                  <input type="checkbox" class="v2-tog-cb" name="<?php echo esc_attr( $opt ); ?>[elementor_auto_sync_classes]" value="1" <?php checked( ! empty( $settings['elementor_auto_sync_classes'] ) ); ?>>
                  <span class="v2-tog<?php echo ! empty( $settings['elementor_auto_sync_classes'] ) ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
                </label>
              </div>
              <div class="v2-sr">
                <div>
                  <div class="v2-sl"><?php esc_html_e( 'Variablen nach Feldtyp filtern', 'ecf-framework' ); ?></div>
                  <div class="v2-sh2"><?php esc_html_e( 'Zeigt nur passende Variablen in Elementor-Feldern (Farbe → Farbfelder)', 'ecf-framework' ); ?></div>
                </div>
                <label class="v2-tog-label">
                  <input type="checkbox" class="v2-tog-cb" name="<?php echo esc_attr( $opt ); ?>[elementor_variable_type_filter]" value="1" <?php checked( ! empty( $settings['elementor_variable_type_filter'] ) ); ?>>
                  <span class="v2-tog<?php echo ! empty( $settings['elementor_variable_type_filter'] ) ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
                </label>
              </div>
            </div>
          </details>
        </div>
      </div>

      <!-- Auto-Klassen für Widgets -->
      <?php
        // Per-widget toggles default to enabled when the master is on but the
        // key was never explicitly set (existing installs upgrading).
        $auto_master_on = ! empty( $settings['auto_classes_enabled'] );
        $auto_default_on = function ( $key ) use ( $settings ) {
            return ! array_key_exists( $key, $settings ) || ! empty( $settings[ $key ] );
        };
        $auto_rows = [
            [
                'key'    => 'auto_classes_headings',
                'widget' => __( 'Heading (h1–h5)', 'ecf-framework' ),
                'class'  => 'ecf-heading-1 … ecf-heading-5',
            ],
            [
                'key'    => 'auto_classes_buttons',
                'widget' => __( 'Button', 'ecf-framework' ),
                'class'  => 'ecf-button',
            ],
            [
                'key'    => 'auto_classes_text_link',
                'widget' => __( 'Text-Link', 'ecf-framework' ),
                'class'  => 'ecf-text-link',
            ],
            [
                'key'    => 'auto_classes_form',
                'widget' => __( 'Form (Elementor Pro)', 'ecf-framework' ),
                'class'  => 'ecf-form',
            ],
        ];
      ?>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Auto-Klassen für Widgets', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <!-- Master-Toggle -->
          <div class="v2-sr">
            <div>
              <div class="v2-sl"><?php esc_html_e( 'Auto-Klassen aktiviert', 'ecf-framework' ); ?></div>
              <div class="v2-sh2"><?php esc_html_e( 'Hängt Layrix-Klassen automatisch an Elementor-Widgets. Body-Text wird nicht verklasst — Größe ist global im System geregelt.', 'ecf-framework' ); ?></div>
            </div>
            <label class="v2-tog-label">
              <input type="checkbox" class="v2-tog-cb" id="v2-auto-classes-master" name="<?php echo esc_attr( $opt ); ?>[auto_classes_enabled]" value="1" <?php checked( $auto_master_on ); ?>>
              <span class="v2-tog<?php echo $auto_master_on ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
            </label>
          </div>

          <!-- Per-Widget-Tabelle -->
          <div class="v2-auto-classes-table<?php echo $auto_master_on ? '' : ' is-disabled'; ?>" id="v2-auto-classes-table">
            <div class="v2-auto-classes-row v2-auto-classes-row--head">
              <div><?php esc_html_e( 'Widget', 'ecf-framework' ); ?></div>
              <div><?php esc_html_e( 'Bekommt Klasse', 'ecf-framework' ); ?></div>
              <div><?php esc_html_e( 'Aktiv', 'ecf-framework' ); ?></div>
            </div>
            <?php foreach ( $auto_rows as $row ) :
                $row_on = $auto_default_on( $row['key'] );
            ?>
            <div class="v2-auto-classes-row">
              <div><?php echo esc_html( $row['widget'] ); ?></div>
              <div><code class="v2-auto-classes-code"><?php echo esc_html( $row['class'] ); ?></code></div>
              <div>
                <label class="v2-tog-label">
                  <input type="checkbox" class="v2-tog-cb" name="<?php echo esc_attr( $opt ); ?>[<?php echo esc_attr( $row['key'] ); ?>]" value="1" <?php checked( $row_on ); ?> <?php disabled( ! $auto_master_on ); ?>>
                  <span class="v2-tog<?php echo $row_on ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
                </label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Elementor Limits -->
      <?php
        $el_snap       = $this->get_elementor_limit_snapshot();
        $el_cls_total  = (int) ( $el_snap['classes_total']   ?? 0 );
        $el_cls_limit  = (int) ( $el_snap['classes_limit']   ?? 100 );
        $el_var_total  = (int) ( $el_snap['variables_total'] ?? 0 );
        $el_var_limit  = (int) ( $el_snap['variables_limit'] ?? 1000 );
        $el_cls_pct    = $el_cls_limit > 0 ? min( 100, (int) round( $el_cls_total / $el_cls_limit * 100 ) ) : 0;
        $el_var_pct    = $el_var_limit > 0 ? min( 100, (int) round( $el_var_total / $el_var_limit * 100 ) ) : 0;
        $el_cls_status = $this->global_class_limit_status( $el_cls_total, $el_cls_limit );
        $el_var_status = $this->global_class_limit_status( $el_var_total, $el_var_limit );
        $el_status_color = [ 'success' => 'var(--ecf-success,#22c55e)', 'warning' => 'var(--ecf-warn,#f59e0b)', 'danger' => 'var(--ecf-danger,#ef4444)' ];
      ?>
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Elementor Limits', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <!-- Classes -->
          <div class="v2-sr" style="flex-direction:column;align-items:stretch;gap:6px">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div class="v2-sl"><?php esc_html_e( 'Globale Klassen', 'ecf-framework' ); ?></div>
              <span style="font-size:12px;font-weight:600;color:<?php echo esc_attr( $el_status_color[ $el_cls_status ] ); ?>"><?php echo esc_html( $el_cls_total . ' / ' . $el_cls_limit ); ?></span>
            </div>
            <div style="background:rgba(255,255,255,.08);border-radius:999px;height:4px;overflow:hidden">
              <div style="height:100%;width:<?php echo esc_attr( $el_cls_pct ); ?>%;background:<?php echo esc_attr( $el_status_color[ $el_cls_status ] ); ?>;border-radius:999px;transition:width .3s"></div>
            </div>
          </div>
          <!-- Variables -->
          <div class="v2-sr" style="flex-direction:column;align-items:stretch;gap:6px">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div class="v2-sl"><?php esc_html_e( 'Globale Variablen', 'ecf-framework' ); ?></div>
              <span style="font-size:12px;font-weight:600;color:<?php echo esc_attr( $el_status_color[ $el_var_status ] ); ?>"><?php echo esc_html( $el_var_total . ' / ' . $el_var_limit ); ?></span>
            </div>
            <div style="background:rgba(255,255,255,.08);border-radius:999px;height:4px;overflow:hidden">
              <div style="height:100%;width:<?php echo esc_attr( $el_var_pct ); ?>%;background:<?php echo esc_attr( $el_status_color[ $el_var_status ] ); ?>;border-radius:999px;transition:width .3s"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Plugin-Updates -->
      <div class="v2-sec">
        <div class="v2-sh"><?php esc_html_e( 'Plugin-Updates', 'ecf-framework' ); ?></div>
        <div class="v2-sg">
          <div class="v2-sr">
            <div class="v2-sl">
              <div class="v2-sl-label"><?php esc_html_e( 'Automatische Updates', 'ecf-framework' ); ?></div>
              <div class="v2-sl-desc"><?php esc_html_e( 'Layrix prüft GitHub auf neue Versionen und stellt Updates über den WordPress-Updater bereit.', 'ecf-framework' ); ?></div>
            </div>
            <label class="v2-tog-label">
              <input type="checkbox"
                     class="v2-tog-cb"
                     name="<?php echo esc_attr( $this->option_name ); ?>[github_update_checks_enabled]"
                     value="1"
                     <?php checked( ! empty( $settings['github_update_checks_enabled'] ) ); ?>>
              <span class="v2-tog<?php echo ! empty( $settings['github_update_checks_enabled'] ) ? ' v2-tog--on' : ' v2-tog--off'; ?>"></span>
            </label>
          </div>
          <div class="v2-sr">
            <div class="v2-sl">
              <div class="v2-sl-label"><?php esc_html_e( 'Installierte Version', 'ecf-framework' ); ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
              <span class="v2-badge">v<?php echo esc_html( $ver ); ?></span>
              <span style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3)">Root: <strong style="color:var(--v2-text2)"><?php echo esc_html( $root_size . '%' ); ?></strong></span>
              <span style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3)">Container: <strong style="color:var(--v2-text2)"><?php echo esc_html( $container_w ); ?></strong></span>
              <span style="font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3)">Text max: <strong style="color:var(--v2-text2)"><?php echo esc_html( $text_max_w ); ?></strong></span>
            </div>
          </div>
        </div>
      </div>

      </div><!-- /v2-st-system -->

    </div><!-- /content -->
  </div>
</div><!-- /settings page -->

<!-- Owner-only Ideen page — submits via REST (no <form> tag), so it lives inside the settings form like any other v2 page and inherits flex layout -->
<?php $this->render_owner_notes_page(); ?>

</form><!-- #ecf-v2-form -->

<!-- Standalone forms (außerhalb ecf-v2-form, kein nesting) -->
<form id="ecf-export-form" method="post" action="<?php echo esc_url( $export_url ); ?>" style="display:none">
  <?php wp_nonce_field( 'ecf_export' ); ?>
  <input type="hidden" name="action" value="ecf_export">
</form>
<form id="ecf-import-form" method="post" action="<?php echo esc_url( $export_url ); ?>" enctype="multipart/form-data" style="display:none">
  <?php wp_nonce_field( 'ecf_import' ); ?>
  <input type="hidden" name="action" value="ecf_import">
  <input type="file" name="ecf_import_file" accept=".json" id="v2-import-file" required style="display:none" data-v2-import-file>
</form>
<form id="ecf-class-cleanup-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none">
  <?php wp_nonce_field( 'ecf_class_cleanup' ); ?>
  <input type="hidden" name="action" value="ecf_class_cleanup">
</form>
<form id="ecf-native-cleanup-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none">
  <?php wp_nonce_field( 'ecf_native_cleanup' ); ?>
  <input type="hidden" name="action" value="ecf_native_cleanup">
</form>

</div><!-- .v2-shell -->

<!-- REM→PX Popover -->
<div id="v2-rfi-popover" style="display:none;position:fixed;z-index:99999;background:var(--v2-surface2);border:1px solid var(--v2-border);border-radius:8px;padding:14px 16px;width:200px;box-shadow:0 6px 20px rgba(0,0,0,.3)">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
    <span style="font-size:var(--v2-ui-base-fs, 13px);font-weight:600;color:var(--v2-text1)"><?php esc_html_e( 'rem → px Referenz', 'ecf-framework' ); ?></span>
    <button type="button" id="v2-rfi-close" style="background:none;border:none;color:var(--v2-text3);cursor:pointer;font-size:13px;padding:0;line-height:1">✕</button>
  </div>
  <div id="v2-rfi-list"></div>
  <p style="font-size:var(--v2-btn-fs, 12px);color:var(--v2-text3);margin:8px 0 0;line-height:1.4"><?php esc_html_e( 'Ändert sich wenn Root Font Size wechselt.', 'ecf-framework' ); ?></p>
</div>

<!-- Konflikt-Modal -->
<div id="v2-conflict-modal" class="v2-modal-overlay" hidden>
  <div class="v2-modal-box" style="width:460px">
    <div class="v2-modal-head">
      <span>⚠ <?php esc_html_e( 'Konflikte erkannt', 'ecf-framework' ); ?></span>
      <button type="button" class="v2-modal-close" id="v2-conflict-cancel">✕</button>
    </div>
    <div class="v2-modal-body">
      <p style="font-size:12px;color:var(--v2-text2);margin:0 0 12px"><?php esc_html_e( 'Folgende Tokens wurden in Elementor verändert und weichen von Layrix ab. Beim Sync überschreibt Layrix diese Werte.', 'ecf-framework' ); ?></p>
      <div id="v2-conflict-list" style="max-height:200px;overflow-y:auto"></div>
    </div>
    <div class="v2-modal-foot">
      <button type="button" class="v2-btn v2-btn--ghost" id="v2-conflict-cancel2"><?php esc_html_e( 'Abbrechen', 'ecf-framework' ); ?></button>
      <button type="button" class="v2-btn v2-btn--primary" id="v2-conflict-confirm"><?php esc_html_e( 'Trotzdem sync', 'ecf-framework' ); ?></button>
    </div>
  </div>
</div>

</div><!-- .ecf-v2-wrapper -->
<div class="v2-toast" id="ecf-v2-toast"></div>
<?php
    }

    private function v2_generate_shades( $hex, $count = 6 ) {
        if ( ! preg_match( '/^#([a-f0-9]{6})$/i', $hex, $m ) ) return [];
        $r = hexdec( substr( $m[1], 0, 2 ) );
        $g = hexdec( substr( $m[1], 2, 2 ) );
        $b = hexdec( substr( $m[1], 4, 2 ) );
        $result = [];
        for ( $i = 1; $i <= $count; $i++ ) {
            $t  = $i / ( $count + 1 );
            $result[] = sprintf( '#%02x%02x%02x',
                (int) round( $r + ( 255 - $r ) * $t ),
                (int) round( $g + ( 255 - $g ) * $t ),
                (int) round( $b + ( 255 - $b ) * $t )
            );
        }
        return $result;
    }

    private function v2_generate_darkshades( $hex, $count = 6 ) {
        if ( ! preg_match( '/^#([a-f0-9]{6})$/i', $hex, $m ) ) return [];
        $r = hexdec( substr( $m[1], 0, 2 ) );
        $g = hexdec( substr( $m[1], 2, 2 ) );
        $b = hexdec( substr( $m[1], 4, 2 ) );
        $result = [];
        for ( $i = $count; $i >= 1; $i-- ) {
            $t  = $i / ( $count + 1 );
            $result[] = sprintf( '#%02x%02x%02x',
                (int) round( $r * $t ),
                (int) round( $g * $t ),
                (int) round( $b * $t )
            );
        }
        return $result;
    }
}
