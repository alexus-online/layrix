<?php
/**
 * Layrix Atomic Section Widget for Elementor v4.
 *
 * Renders an outer container with `ecf-section` plus an automatically-created
 * locked inner Div_Block child with `ecf-section__inner`. Users drop content
 * into the inner Div_Block, which provides the boxed container max-width.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
    return;
}

if ( ! class_exists( 'ECF_Atomic_Section' ) ) {

    class ECF_Atomic_Section extends \Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base {

        const BASE_STYLE_KEY = 'base';

        public function __construct( $data = [], $args = null ) {
            parent::__construct( $data, $args );
            if ( method_exists( $this, 'meta' ) ) {
                $this->meta( 'is_container', true );
            }
        }

        public static function get_type() {
            return 'e-layrix-section';
        }

        public static function get_element_type(): string {
            return 'e-layrix-section';
        }

        public function get_title() {
            return __( 'Layrix Section', 'ecf-framework' );
        }

        public function get_keywords() {
            return [ 'layrix', 'section', 'ecf-section', 'wrapper', 'container' ];
        }

        public function get_icon() {
            return 'eicon-section';
        }

        protected static function define_props_schema(): array {
            return [
                'classes' => \Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type::make()
                    ->default( [] ),
                'attributes' => \Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type::make(),
            ];
        }

        protected function define_atomic_controls(): array {
            return [
                \Elementor\Modules\AtomicWidgets\Controls\Section::make()
                    ->set_label( __( 'Settings', 'ecf-framework' ) )
                    ->set_id( 'settings' )
                    ->set_items( [
                        \Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control::bind_to( '_cssid' )
                            ->set_label( __( 'ID', 'ecf-framework' ) )
                            ->set_meta( $this->get_css_id_control_meta() ),
                    ] ),
            ];
        }

        protected function define_default_html_tag() {
            return 'div';
        }

        protected function define_base_styles(): array {
            return [
                static::BASE_STYLE_KEY => \Elementor\Modules\AtomicWidgets\Styles\Style_Definition::make()
                    ->add_variant(
                        \Elementor\Modules\AtomicWidgets\Styles\Style_Variant::make()
                            ->add_prop( 'display', \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate( 'block' ) )
                            ->add_prop( 'min-width', \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [
                                'size' => 30,
                                'unit' => 'px',
                            ] ) )
                    ),
            ];
        }

        /**
         * Auto-create one locked inner Div_Block child carrying ecf-section__inner.
         * The user can drop content into the inner block; it cannot be deleted.
         */
        protected function define_default_children() {
            if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Div_Block\Div_Block' ) ) {
                return [];
            }
            // Layrix syncs starter classes to Elementor's Global Classes registry
            // with deterministic IDs ('g-ecf-' . substr(md5(label), 0, 10)).
            $inner_class_id = 'g-ecf-' . substr( md5( 'ecf-container-boxed' ), 0, 10 );
            $inner_settings = [
                'classes' => \Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type::generate( [ $inner_class_id ] ),
            ];
            $inner = \Elementor\Modules\AtomicWidgets\Elements\Div_Block\Div_Block::generate()
                ->settings( $inner_settings )
                ->editor_settings( [
                    'title' => __( 'Section Inner', 'ecf-framework' ),
                ] )
                ->build();

            // The per-widget max-width var-ref is applied via JS after creation
            // (atomic-section-editor.js) — Elementor's JS-side buildElement()
            // strips the `styles` key from default_children output, so setting
            // styles here in PHP would not persist.

            return [ $inner ];
        }

        protected function add_render_attributes() {
            parent::add_render_attributes();
            $settings = $this->get_atomic_settings();
            $base_style_class = $this->get_base_styles_dictionary()[ static::BASE_STYLE_KEY ];
            $initial_attributes = $this->define_initial_attributes();

            $attributes = [
                'class' => [
                    'e-con',
                    'e-atomic-element',
                    'ecf-layrix-section',
                    $base_style_class,
                    ...( $settings['classes'] ?? [] ),
                ],
            ];

            if ( ! empty( $settings['_cssid'] ) ) {
                $attributes['id'] = esc_attr( $settings['_cssid'] );
            }

            $this->add_render_attribute( '_wrapper', array_merge( $initial_attributes, $attributes ) );
        }
    }

}
