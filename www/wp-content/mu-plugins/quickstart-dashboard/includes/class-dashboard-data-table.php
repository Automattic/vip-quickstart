<?php

if( ! class_exists( 'WP_List_Table' ) ){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class DashboardDataTable extends DashboardWidgetTable {

	private $columns = array();
	private $escape_output = true;
	private $show_check_column = true;

	function __construct( $cols, $items ) {
		$this->items = $items;
		$this->columns = $cols;

        parent::__construct( array(
            'singular'  => 'action',
            'plural'    => 'actions',
            'ajax'      => false
        ) );
    }

	function disable_output_escaping() {
		$this->escape_output = false;
	}

	function show_check_column( $show ) {
		$this->show_check_column = $show;
	}

	function display() {
		extract( $this->_args );

?>
<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
	<thead>
	<tr>
		<?php $this->print_column_headers( false ); ?>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<?php $this->print_column_headers( false ); ?>
	</tr>
	</tfoot>

	<tbody id="the-list"<?php if ( $singular ) echo " data-wp-lists='list:$singular'"; ?>>
		<?php $this->display_rows_or_placeholder(); ?>
	</tbody>
</table>
<?php
	}

	function column_default( $item, $column_name ){
		return $this->escape_output ? esc_html( $item['data'][$column_name] ) : $item['data'][$column_name];
    }

	function column_cb( $item ){
		// Try and figure out this items' id
		$id = '';
		if ( isset( $item['id'] ) ) {
			$id = $item['id'];
		} elseif ( isset( $item['data']['id'] ) ) {
			$id = $item['data']['id'];
		} elseif ( isset( $item['data'][$this->_args['singular'] . '_id'] ) ) {
			$id = $item['data'][$this->_args['singular'] . '_id'];
		}

        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],
            /*$2%s*/ $id
        );
    }

	function get_columns() {
		if ( $this->show_check_column ) {
			return array_merge(
					array( 'cb' => '<input type="checkbox" />' ),
					$this->columns
				);
		} else {
			return $this->columns;
		}
    }

	function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

		$total_items = count( $this->items );
		$per_page = 10;
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items/$per_page ),
        ) );
    }

	function single_row( $item ) {
		$row_classes = parent::get_row_classes();

		if ( isset( $item['warn'] ) && $item['warn'] ) {
			$row_classes[] = 'warn';
		} elseif ( isset( $item['active'] ) && $item['active'] ) {
			$row_classes[] = 'active';
		} else {
			$row_classes[] = 'inactive';
		}

		echo '<tr class="' . implode( ' ', $row_classes ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';

		// Check if the item needs updating. If it can't be updated, tell the user to fix the problem.
		if ( isset( $item['warn'] ) && $item['warn'] && isset( $item['message']) ) {
			$message = $this->escape_output ? esc_html( $item['message'] ) : $item['message'];
			printf( '<tr class="plugin-update-tr"><td class="plugin-update colspanchange" colspan="%s"><div class="update-message">%s</div></td></tr>', $this->get_column_count(), wp_kses( $message, wp_kses_allowed_html( 'post' ) ) );
		}
	}
}