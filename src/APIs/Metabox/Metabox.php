<?php
/**
 * Metabox API
 *
 * @package FORMNOTIFY\APIs\Metabox
 */

namespace FORMNOTIFY\APIs\Metabox;

/**
 * Metabox API class
 */
class Metabox {

	const BLOCK_NAMESPACE                  = 'form-notify-box'; // (A.K.A "Metabox Constructor Class")
	const REPEATER_INDEX_PLACEHOLDER       = 'CurrentCounter';
	const REPEATER_ITEM_NUMBER_PLACEHOLDER = 'ItemNumber';

	/**
	 * Stores the metabox configuration.
	 *
	 * @var array
	 */
	private array $meta_box;

	/**
	 * Stores the folder name.
	 *
	 * @var string
	 */
	private string $folder_name;

	/**
	 * Stores the path.
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Stores the nonce name.
	 *
	 * @var string
	 */
	private string $nonce_name;

	/**
	 * Stores the fields supplied to the
	 * metabox.
	 *
	 * @var array
	 */
	private array $fields;

	/**
	 * Class constructor.
	 *
	 * @param array $meta_box_config The metabox configuration.
	 *
	 * @return void
	 */
	public function __construct( array $meta_box_config ) {

		$defaults = array(
			'context'  => 'advanced',
			'priority' => 'default',
		);

		$this->fields      = array();
		$this->meta_box    = array_merge( $defaults, $meta_box_config );
		$this->nonce_name  = $meta_box_config['id'] . '_nonce';
		$this->folder_name = 'Metabox-constructor-class';
		$this->path        = plugins_url( $this->folder_name, plugin_basename( dirname( __FILE__ ) ) );

		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
	}

	/**
	 * Enqueues the scripts and stylesheets.
	 *
	 * @return void
	 */
	public function scripts(): void {
		global $typenow;

		wp_enqueue_media();

		if (
			( is_array( $this->meta_box['screen'] ) && in_array( $typenow, $this->meta_box['screen'], true ) ) ||
			( is_string( $this->meta_box['screen'] ) && $typenow === $this->meta_box['screen'] )
		) {
			wp_enqueue_style( 'Metabox-style', plugin_dir_url( __FILE__ ) . 'assets/style.css', array(), '1.0.1', null );
			wp_enqueue_script( 'Metabox-script', plugin_dir_url( __FILE__ ) . 'assets/script.js', array( 'jquery' ), '1.0.0', true );
		}
	}

	/**
	 * Adds the metabox to the post editor.
	 *
	 * @return void
	 */
	public function add(): void {
		add_meta_box(
			$this->meta_box['id'],
			$this->meta_box['title'],
			array( $this, 'show' ),
			$this->meta_box['screen'],
			$this->meta_box['context'],
			$this->meta_box['priority']
		);
	}

	/**
	 * An aggregate function that shows tye contents of the metabox
	 * by calling the appropriate, individual function for each
	 * field type.
	 *
	 * @return void
	 */
	public function show(): void {
		global $post;

		wp_nonce_field( basename( __FILE__ ), $this->nonce_name );
		echo sprintf( '<div class="%s">', esc_attr( self::BLOCK_NAMESPACE ) );
		if ( $this->fields ) {
			foreach ( $this->fields as $field ) {
				$meta = get_post_meta( $post->ID, $field['id'], true );
				call_user_func( array( $this, 'show_field_' . $field['type'] ), $field, $meta );
			}
		}
		echo '</div>';
	}

	/**
	 * Saves the data supplied to the metabox.
	 *
	 * @return void
	 */
	public function save(): void {
		global $post_id, $post;

		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || // prevent the data from being auto-saved.
			( ! current_user_can( 'edit_post', $post_id ) ) || // check user permissions.
			( ( ! isset( $_POST[ $this->nonce_name ] ) ) ) || // verify nonce (same with below).
			( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $this->nonce_name ] ) ), basename( __FILE__ ) ) )
		) {
			return;
		}

		foreach ( $this->fields as $field ) {
			if ( isset( $_POST[ $field['id'] ] ) ) {
				$post_field_id = $this->sanitize_recursive( $_POST[ $field['id'] ] );

				if ( 'text' === $field['type'] || 'textarea' === $field['type'] ) {
					update_post_meta( $post->ID, $field['id'], $post_field_id );
				} elseif ( 'multiselect' === $field['type'] ) {
					update_post_meta( $post->ID, $field['id'], wp_json_encode( $post_field_id ) );
				} else {
					update_post_meta( $post->ID, $field['id'], $post_field_id ); // phpcs:ignore
				}
			} else {
				delete_post_meta( $post->ID, $field['id'] );
			}
		}
	}

	public function sanitize_recursive( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = $this->sanitize_recursive( $value );
			}
		} else {
			$data = sanitize_text_field( $data );
		}

		return $data;
	}

	/**
	 * Returns a formatted string for a column class name.
	 *
	 * @param int   $width    The width of the column.
	 * @param mixed $contents The contents of the column.
	 *
	 * @return string
	 */
	public function column( int $width, mixed $contents ): string {
		if ( isset( $width, $contents ) ) {
			return sprintf(
				'<div class="%s %s">%s</div>',
				esc_attr( $this->get_element_class_with_namespace( 'col' ) ),
				esc_attr( $this->get_element_class_with_namespace( sprintf( 'col-%d', $width ) ) ),
				esc_html( $contents )
			);
		}

		return '';
	}

	/**
	 * Returns a formatted string for a block-element (block__element) class name.
	 *
	 * @param string $block   The block name.
	 * @param string $element The element name.
	 *
	 * @return string
	 */
	public function get_block_element_class( string $block, string $element ): string {
		if ( isset( $block, $element ) ) {
			return trim( sprintf( '%s__%s', $block, $element ) );
		}
	}

	/**
	 * Returns a formatted string for a block-element (block__element) class name
	 * of a field element or non-field element prefixed with the namespace.
	 *
	 * @param string  $element  The element name.
	 * @param boolean $is_field Whether the element is a field element or not.
	 *
	 * @return string
	 */
	public function get_block_element_class_with_namespace( string $element, bool $is_field = true ): string {
		if ( isset( $element ) ) {
			return trim(
				sprintf(
					'%s %s%s',
					( $is_field
						? ( sprintf( '%s__%s', self::BLOCK_NAMESPACE, 'field' ) )
						: ''
					),
					sprintf( '%s__%s', self::BLOCK_NAMESPACE, ( $is_field ? 'field-' : '' ) ),
					$element
				)
			);
		}
	}

	/**
	 * Returns a formatted string for a class name prefixed with
	 * the namespace.
	 *
	 * @param string $suffix The suffix of the class name.
	 *
	 * @return string
	 */
	public function get_element_class_with_namespace( string $suffix ): string {
		if ( isset( $suffix ) ) {
			return trim(
				sprintf(
					'%s-%s',
					self::BLOCK_NAMESPACE,
					$suffix
				)
			);
		}

		return '';
	}

	/**
	 * Echos some HTML that precedes a field (container, label, description, etc.)
	 *
	 * @param array         $field The field configuration.
	 * @param string | null $meta  The meta value of the field.
	 */
	public function before_field( array $field, string $meta = null ): void {

		$class = array_key_exists( 'class', $field ) ? $field['class'] : '';

		echo sprintf(
			'<div class="%s %s %s">',
			esc_attr( $this->get_block_element_class_with_namespace( 'field-container', false ) ),
			esc_attr( $this->get_block_element_class_with_namespace( $field['type'] . '-container', false ) ),
			esc_attr( $class )
		);

		if ( isset( $field['label'] ) ) {
			echo sprintf(
				'<label class="%s" for="%s">%s</label>',
				esc_attr( $this->get_block_element_class_with_namespace( 'label', false ) ),
				esc_attr( $field['id'] ),
				esc_html( $field['label'] )
			);
		}

		echo '<div>';

		if ( 'image' === $field['type'] ) {
			$this->get_image_preview( $field, $meta );
		}
	}

	/**
	 * Echos HTML that comes after a field (container, description, etc).
	 *
	 * @param array | null $field The field configuration.
	 */
	public function after_field( array $field = null ): void {
		if ( isset( $field['desc'] ) ) {
			$this->get_field_description( $field['desc'] );
		}
		echo '</div></div>';
	}

	/**
	 * Echos a paragraph element with some description text that
	 * serves as an assistant to the operator of the metabox.
	 *
	 * @param string $desc The description text.
	 */
	public function get_field_description( string $desc ): void {
		echo sprintf(
			'<p class="%s">%s</p>',
			esc_attr( $this->get_block_element_class_with_namespace( 'description', false ) ),
			esc_html( $desc )
		);
	}

	/**
	 * Echos an image tag that serves as preview.
	 *
	 * @param array  $field The field configuration.
	 * @param string $meta  The meta value of the field.
	 */
	public function get_image_preview( array $field, string $meta ): void {
		global $post;

		echo sprintf(
			'<img id="%s" class="%s" src="%s" alt="%s">',
			esc_attr( sprintf( 'js-%s-image-preview', $field['id'] ) ),
			esc_attr( sprintf( '%s %s', $this->get_block_element_class_with_namespace( 'image-preview', false ), empty( $meta ) ? 'is-hidden' : '' ) ),
			esc_attr( $meta ),
			esc_attr( '' )
		);
	}

	/**
	 * Add html field
	 *
	 * @param array $args     Field arguments.
	 * @param bool  $repeater Whether the field is a repeater or not.
	 *
	 * @return array
	 */
	public function add_html( array $args, bool $repeater = false ): array {
		$field = array_merge( array( 'type' => 'html' ), $args );
		if ( ! $repeater ) {
			$this->fields[] = $field;
		}

		return $field;
	}

	/**
	 * Add text field
	 *
	 * @param array $args     Field arguments.
	 * @param bool  $repeater Whether the field is a repeater or not.
	 *
	 * @return array
	 */
	public function add_text( array $args, bool $repeater = false ): array {
		$field = array_merge( array( 'type' => 'text' ), $args );
		if ( ! $repeater ) {
			$this->fields[] = $field;
		}

		return $field;
	}

	/**
	 * Add textarea field
	 *
	 * @param array $args     Field arguments.
	 * @param bool  $repeater Whether the field is a repeater or not.
	 *
	 * @return array
	 */
	public function add_textarea( array $args, bool $repeater = false ): array {
		$field = array_merge( array( 'type' => 'textarea' ), $args );
		if ( ! $repeater ) {
			$this->fields[] = $field;
		}

		return $field;
	}

	/**
	 * Add checkbox field
	 *
	 * @param array $args     Field arguments.
	 * @param bool  $repeater Whether the field is a repeater or not.
	 *
	 * @return array
	 */
	public function add_checkbox( array $args, bool $repeater = false ): array {
		$field = array_merge( array( 'type' => 'checkbox' ), $args );
		if ( ! $repeater ) {
			$this->fields[] = $field;
		}

		return $field;
	}

	/**
	 * Add image field
	 *
	 * @param array $args     Field arguments.
	 * @param bool  $repeater Whether the field is a repeater or not.
	 *
	 * @return array
	 */
	public function add_image( array $args, bool $repeater = false ): array {
		$field = array_merge( array( 'type' => 'image' ), $args );
		if ( ! $repeater ) {
			$this->fields[] = $field;
		}

		return $field;
	}

	/**
	 * Add editor field
	 *
	 * @param array $args     Field arguments.
	 * @param bool  $repeater Whether the field is a repeater or not.
	 *
	 * @return array
	 */
	public function add_editor( array $args, bool $repeater = false ): array {
		$field = array_merge( array( 'type' => 'Editor' ), $args );
		if ( ! $repeater ) {
			$this->fields[] = $field;
		}

		return $field;
	}

	/**
	 * Add radio field
	 *
	 * @param array $args     Field arguments.
	 * @param array $options  Field options.
	 * @param bool  $repeater Whether the field is a repeater or not.
	 *
	 * @return array
	 */
	public function add_radio( array $args, array $options, bool $repeater = false ): array {
		$options = array( 'options' => $options );
		$field   = array_merge( array( 'type' => 'radio' ), $args, $options );
		if ( ! $repeater ) {
			$this->fields[] = $field;
		}

		return $field;
	}

	/**
	 * Add select field
	 *
	 * @param array $args     Field arguments.
	 * @param array $options  Field options.
	 * @param bool  $repeater Whether the field is a repeater or not.
	 *
	 * @return array
	 */
	public function add_select( array $args, array $options, bool $repeater = false ): array {
		$options = array( 'options' => $options );
		$field   = array_merge( array( 'type' => 'select' ), $args, $options );
		if ( ! $repeater ) {
			$this->fields[] = $field;
		}

		return $field;
	}

	/**
	 * Add multi select field
	 *
	 * @param array $args     Field arguments.
	 * @param array $options  Field options.
	 * @param bool  $repeater Whether the field is a repeater or not.
	 *
	 * @return array
	 */
	public function add_multi_select( array $args, array $options, bool $repeater = false ): array {
		$options = array( 'options' => $options );
		$field   = array_merge( array( 'type' => 'multiselect' ), $args, $options );
		if ( ! $repeater ) {
			$this->fields[] = $field;
		}

		return $field;
	}

	/**
	 * Add repeater block
	 *
	 * @param array $args Field arguments.
	 *
	 * @return void
	 */
	public function add_repeater_block( array $args ): void {
		$field          = array_merge(
			array(
				'type'         => 'repeater',
				'single_label' => 'Item',
				'is_sortable'  => true,
			),
			$args
		);
		$this->fields[] = $field;
	}

	/**
	 * Show html field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_html( array $field, array|string $meta ): void {
		$this->before_field( $field );
		if ( ! empty( $field['show_value'] ) && ! empty( $field['show_by'] ) ) {
			echo '<div data-show-by="' . esc_attr( $field['show_by'] ) . '" data-show-value="' . esc_attr( $field['show_value'] ) . '">' . wp_kses_post( $field['html'] ) . '</div>';
		} else {
			echo '<div>' . wp_kses_post( $field['html'] ) . '</div>';
		}
		$this->after_field();
	}

	/**
	 * Show text field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_text( array $field, array|string $meta ): void {
		$this->before_field( $field );
		$class = ( array_key_exists( 'class', $field ) ) ? $field['class'] : '';
		if ( ! empty( $field['show_value'] ) && ! empty( $field['show_by'] ) ) {
			echo sprintf(
				'<input type="text" class="%1$s" id="%2$s" name="%2$s" value="%3$s" placeholder="%4$s" data-show-by="' . esc_attr( $field['show_by'] ) . '" data-show-value="' . esc_attr( $field['show_value'] ) . '"><p class="mt:3">%5$s</p>',
				esc_attr( $this->get_block_element_class_with_namespace( $field['type'] ) . ' ' . $class ),
				esc_attr( $field['id'] ),
				esc_attr( $meta ),
				esc_attr( ( array_key_exists( 'placeholder', $field ) ) ? $field['placeholder'] : '' ),
				esc_attr( ( array_key_exists( 'desc', $field ) ) ? $field['desc'] : '' )
			);
		} else {
			echo sprintf(
				'<input type="text" class="%1$s" id="%2$s" name="%2$s" value="%3$s" placeholder="%4$s"><p class="mt:3">%5$s</p>',
				esc_attr( $this->get_block_element_class_with_namespace( $field['type'] ) . ' ' . $class ),
				esc_attr( $field['id'] ),
				esc_attr( $meta ),
				esc_attr( ( array_key_exists( 'placeholder', $field ) ) ? $field['placeholder'] : '' ),
				wp_kses_post( ( array_key_exists( 'desc', $field ) ) ? $field['desc'] : '' )
			);
		}

		$this->after_field();
	}

	/**
	 * Show textarea field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_textarea( array $field, array|string $meta ): void {
		$this->before_field( $field );
		echo sprintf(
			'<textarea class="%1$s" id="%2$s" name="%2$s">%3$s</textarea>',
			esc_attr( $this->get_block_element_class_with_namespace( $field['type'] ) ),
			esc_attr( $field['id'] ),
			esc_html( $meta )
		);
		$this->after_field();
	}

	/**
	 * Show checkbox field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_checkbox( array $field, array|string $meta ): void {
		$this->before_field( $field );
		echo sprintf(
			'<input type="checkbox" class="%1$s" id="%2$s" name="%2$s" %3$s>',
			esc_attr( $this->get_block_element_class_with_namespace( $field['type'] ) ),
			esc_attr( $field['id'] ),
			checked( ! empty( $meta ), true, false )
		);
		$this->after_field( $field );
	}

	/**
	 * Show image field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_image( array $field, array|string $meta ): void {
		$this->before_field( $field, $meta );
		echo sprintf(
			'<input type="hidden" id="%s" name="%s" value="%s">',
			esc_attr( 'image-' . $field['id'] ),
			esc_attr( $field['id'] ),
			esc_attr( $meta )
		);
		echo sprintf(
			'<a class="%s button" data-hidden-input="%s">%s</a>',
			esc_attr( sprintf( 'js-%s-image-upload-button', self::BLOCK_NAMESPACE ) ),
			esc_attr( $field['id'] ),
			esc_html( sprintf( '%s Image', empty( $meta ) ? 'Upload' : 'Change' ) )
		);
		$this->after_field();
	}

	/**
	 * Show editor field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_editor( array $field, array|string $meta ): void {
		$this->before_field( $field );
		wp_editor( $meta, $field['id'] );
		$this->after_field();
	}

	/**
	 * Show radio field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_radio( array $field, array|string $meta ): void {
		$this->before_field( $field );
		foreach ( $field['options'] as $key => $value ) {
			echo sprintf(
				'
                    <label for="%1$s">%2$s</label>
                    <input type="radio" class="%3$s" id="%1$s" name="%4$s" value="%5$s" %6$s>
                ',
				esc_attr( $field['id'] . '_' . $key ),
				esc_html( $value ),
				esc_attr( $this->get_block_element_class_with_namespace( $field['type'] ) ),
				esc_attr( $field['id'] ),
				esc_attr( $key ),
				checked( $key === $meta, true, false )
			);
		}
		$this->after_field( $field );
	}

	/**
	 * Show select field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_select( array $field, array|string $meta ): void {
		$this->before_field( $field );
		if ( ! empty( $field['show_value'] ) && ! empty( $field['show_by'] ) ) {
			echo '<select name="' . esc_attr( $field['id'] ) . '" class="wc-enhanced-select" data-show-by="' . esc_attr( $field['show_by'] ) . '" data-show-value="' . esc_attr( $field['show_value'] ) . '">';
		} else {
			echo '<select name="' . esc_attr( $field['id'] ) . '" class="wc-enhanced-select">';
		}
		foreach ( $field['options'] as $key => $value ) {
			echo sprintf(
				'
                    <option class="%3$s" id="%1$s" name="%4$s" value="%5$s" %6$s>%2$s</option>
                ',
				esc_attr( $field['id'] . '_' . $key ),
				esc_html( $value ),
				esc_attr( $this->get_block_element_class_with_namespace( $field['type'] ) ),
				esc_attr( $field['id'] ),
				esc_attr( $key ),
				selected( $key == $meta, true, false )
			);
		}

		echo '</select>';
		$this->after_field( $field );
	}

	/**
	 * Show multi select field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_multiselect( array $field, array|string $meta ): void {
		$this->before_field( $field );
		$meta = json_decode( $meta );
		if ( ! empty( $field['show_value'] ) && ! empty( $field['show_by'] ) ) {
			echo '<select name="' . esc_attr( $field['id'] ) . '[]" class="wc-enhanced-select" data-show-by="' . esc_attr( $field['show_by'] ) . '" data-show-value="' . esc_attr( $field['show_value'] ) . '" multiple="multiple">';
		} else {
			echo '<select name="' . esc_attr( $field['id'] ) . '[]" class="wc-enhanced-select" multiple="multiple">';
		}

		foreach ( $field['options'] as $key => $value ) {
			echo sprintf(
				'
                    <option class="%3$s" id="%1$s" name="%4$s" value="%5$s" %6$s>%2$s</option>
                ',
				esc_attr( $field['id'] . '_' . $key ),
				esc_html( $value ),
				esc_attr( $this->get_block_element_class_with_namespace( $field['type'] ) ),
				esc_attr( $field['id'] ),
				esc_attr( $key ),
				esc_attr( ( is_array( $meta ) ) ? selected( in_array( $key, $meta, true ), true, false ) : selected( $key === $meta, true, false ) ),
			);
		}
		echo '</select>';
		$this->after_field( $field );
	}

	/**
	 * Show repeater field
	 *
	 * @param array        $field Field arguments.
	 * @param array|string $meta  Field meta value.
	 *
	 * @return void
	 */
	public function show_field_repeater( array $field, array|string $meta ): void {
		$this->before_field( $field );

		if ( ! empty( $field['show_value'] ) && ! empty( $field['show_by'] ) ) {
			echo sprintf(
				'<div id="%s" class="%s" data-show-by="' . esc_attr( $field['show_by'] ) . '" data-show-value="' . esc_attr( $field['show_value'] ) . '">',
				esc_attr( sprintf( 'js-%s-repeated-blocks', $field['id'] ) ),
				esc_attr( $this->get_block_element_class_with_namespace( 'repeated-blocks', false ) )
			);
		} else {
			echo sprintf(
				'<div id="%s" class="%s">',
				esc_attr( sprintf( 'js-%s-repeated-blocks', $field['id'] ) ),
				esc_attr( $this->get_block_element_class_with_namespace( 'repeated-blocks', false ) )
			);
		}

		$count = 0;
		if ( is_array( $meta ) ) {
			if ( count( $meta ) > 0 ) {
				foreach ( $meta as $m ) {
					$this->get_repeated_block( $field, $m, $count );
					$count ++;
				}
			} else {
				$this->get_repeated_block( $field, '', $count );
			}
		}

		echo '</div>';

		// "add" button
		echo sprintf(
			'<a id="%s" class="%s button">
                    <span class="dashicons dashicons-plus"></span>
                    %s
                </a>',
			esc_attr( sprintf( 'js-%s-add', $field['id'] ) ),
			esc_attr( $this->get_block_element_class_with_namespace( 'add', false ) ),
			esc_html( sprintf( '%s', $field['button_label'] ) )
		);

		$this->after_field();

		ob_start();

		sprintf( '<div>%s</div>', esc_html( $this->get_repeated_block( $field, $meta, null, true ) ) );

		$js_code = ob_get_clean();
		$js_code = str_replace( "\n", '', $js_code );
		$js_code = str_replace( "\r", '', $js_code );
		$js_code = str_replace( "'", '"', $js_code );

		/**
		 * JS to add another repeated block
		 */
		$count       = max( 1, $count );
		$field_id    = $field['id'];
		$index       = self::REPEATER_INDEX_PLACEHOLDER;
		$item_number = self::REPEATER_ITEM_NUMBER_PLACEHOLDER;

		wp_enqueue_script( 'form-notify-repeater', FORMNOTIFY_PLUGIN_URL . '/assets/src/repeater.js', array( 'jquery' ), '1.0.0', true );

		wp_localize_script(
			'form-notify-repeater',
			'repeaterData',
			array(
				'count'                   => esc_html( $count ),
				'field_id'                => esc_html( $field_id ),
				'js_code'                 => esc_html( $js_code ),
				'index_placeholder'       => esc_html( $index ),
				'item_number_placeholder' => esc_html( $item_number ),
			)
		);
	}

	/**
	 * Show repeated block
	 *
	 * @param array        $field       Field arguments.
	 * @param array|string $meta        Field meta value.
	 * @param int          $index       The index of the repeated block.
	 * @param bool         $is_template Whether the block is a template or not.
	 *
	 * @return void
	 */
	public function get_repeated_block( $field, $meta, $index, $is_template = false ) {

		echo sprintf(
			'<div class="%s">',
			esc_attr( $this->get_block_element_class_with_namespace( 'repeated', false ) )
		);

		echo sprintf(
			'<div class="%s %s">
                    <p class="%s">%s</p>
                    <ul class="%s">
                        <li>
                            <a class="%s %s" title="%s">
                                <span class="dashicons dashicons-no"></span>
                            </a>
                        </li>
                        <li>
                            <a class="%s %s" title="Click and drag to sort">
                                <span class="dashicons dashicons-menu"></span>
                            </a>
                        </li>
                    </ul>
                </div>',
			esc_attr( $this->get_element_class_with_namespace( 'repeated-header', false ) ),
			esc_attr( $this->get_element_class_with_namespace( 'clearfix' ) ),
			esc_attr( sprintf( '%s %s %s', $this->get_block_element_class( 'repeated-header', 'title' ), $this->get_element_class_with_namespace( 'col' ), $this->get_element_class_with_namespace( 'col-6' ) ) ),
			esc_html( sprintf( '%s ' . ( $is_template ? '%s' : '%d' ), $field['single_label'], ( $is_template ? self::REPEATER_ITEM_NUMBER_PLACEHOLDER : $index + 1 ) ) ),
			esc_attr( sprintf( '%s %s %s', $this->get_block_element_class( 'repeated-header', 'nav' ), $this->get_element_class_with_namespace( 'col' ), $this->get_element_class_with_namespace( 'col-6' ) ) ),
			esc_attr( $this->get_block_element_class_with_namespace( 'repeater-button', false ) ),
			esc_attr( $this->get_block_element_class_with_namespace( 'remove', false ) ),
			esc_attr( sprintf( 'Remove %s', $field['single_label'] ) ),
			esc_attr( $this->get_block_element_class_with_namespace( 'repeater-button', false ) ),
			esc_attr( sprintf( 'js-%s-sort', self::BLOCK_NAMESPACE ) )
		);

		echo sprintf( '<div class="%s is-hidden">', esc_attr( $this->get_block_element_class_with_namespace( 'repeated-content', false ) ) );

		foreach ( $field['fields'] as $child_field ) {
			$old_id = $child_field['id'];

			$child_field['id'] = sprintf(
				'%s[%s][%s]',
				$field['id'],
				( $is_template ? self::REPEATER_INDEX_PLACEHOLDER : $index ),
				$child_field['id']
			);

			$child_meta = isset( $meta[ $old_id ] ) && ! $is_template ? $meta[ $old_id ] : '';

			call_user_func( array( $this, 'show_field_' . $child_field['type'] ), $child_field, $child_meta );
		}
		echo '</div></div>';
	}

}
