add_filter( 'manage_edit-product_columns', 'customise_columns' );
function customise_columns( $columns ){
	$new_columns = ( is_array( $columns ) ) ? $columns : array();
	
	//Remove columns we don't need
  	unset( $new_columns[ 'sku' ] );
	unset($new_columns['product_tag']);
	unset($new_columns['featured']);
	unset($new_columns['date']);
		unset($new_columns['is_in_stock']);


	//Add an empty column for variation level stock
	return array_slice( $new_columns, 0, 3, true )
	+ array( 'var_stock' => 'Variation Stock' )
	+ array( 'var_stock_summary' => 'Stock' )

	+ array_slice( $new_columns, 3, NULL, true );

}



add_action( 'manage_posts_custom_column', 'dado_populate_variation_stock' );
function dado_populate_variation_stock( $column_name ) {
 
	if( $column_name  == 'var_stock' ) {
		$productId =  get_the_ID(); // taxonomy name
		$product = wc_get_product($productId);
		
		 if ( $product->get_type() == 'variable' ) {
        foreach ( $product->get_available_variations() as $key ) {
            $attr_string = array();
            foreach ( $key['attributes'] as $attr_name=> $attr_title ) {
				$attr_title_formatted = str_replace('-',' ',$attr_title);

                $attr_string[] = $attr_title_formatted;
            }
            if ( $key['max_qty'] < 4 and $key['max_qty'] >0) { 
               echo '<div style="display:flex; flex-wrap:nowrap; align-items:center;"> <div style="border-radius:50px; width:10px; height:10px; background-color:orange; margin-right:5px;"></div>' . implode( ', ', $attr_string ) . ': ' . $key['max_qty'] . ' In Stock</div>'; 
            } 
			
			elseif($key['max_qty']<=0){
              echo '<div style="display:flex; flex-wrap:nowrap; align-items:center;"><div style="border-radius:50px; width:10px; height:10px; background-color:red; margin-right:5px;"></div>' . implode(', ', $attr_string ) . ': Out Of Stock</div>'; 

			}
			else { 
              echo '<div style="display:flex; flex-wrap:nowrap; align-items:center;"> <div style="border-radius:50px; width:10px; height:10px; background-color:green; margin-right:5px;"></div>' . implode( ', ', $attr_string ) . ': ' . $key['max_qty'] . ' In Stock</div>'; 
            }
        }
    }
		//Assumed Single product
		else{
		$stock = $product->get_stock_quantity();
		if ( $stock < 4 and $stock >0) { 
               echo '<div style="display:flex; flex-wrap:nowrap; align-items:center;"> <div style="border-radius:50px; width:10px; height:10px; background-color:orange; margin-right:5px;"></div> ' . $stock . ' In Stock</div>'; 
            } 
			
			elseif($stock<=0){
              echo '<div style="display:flex; flex-wrap:nowrap; align-items:center;"><div style="border-radius:50px; width:10px; height:10px; background-color:red; margin-right:5px;"></div> Out Of Stock</div>'; 

			}
			else { 
              echo '<div style="display:flex; flex-wrap:nowrap; align-items:center;"> <div style="border-radius:50px; width:10px; height:10px; background-color:green; margin-right:5px;"></div> ' . $stock . ' In Stock</div>'; 
            }
			
		}
		
		echo $x;
	}
	
	if( $column_name  == 'var_stock_summary' ) {
		$productId =  get_the_ID(); // taxonomy name
		$product = wc_get_product($productId);
		
		 if ( $product->get_type() == 'variable' ) {
			 $numberLowStock=0;
			 $numberOutOfStock=0;

        foreach ( $product->get_available_variations() as $key ) {
            $attr_string = array();
            foreach ( $key['attributes'] as $attr_name => $attr_value ) {
                $attr_string[] = $attr_value;
            }
            if ( $key['max_qty'] < 4 and $key['max_qty'] >0) { 
		$numberLowStock+=1;
            } 
			
			if($key['max_qty']==0){
				$numberOutOfStock+=1;
			}

        }
			 if($numberLowStock>0 and $numberOutOfStock==0){
				 $variation_or_variations="Variations";
				 if($numberLowStock==1){$variation_or_variations="Variation";}
				 echo '<div style="color:orange;">Low Stock | '.$numberLowStock.' '.$variation_or_variations.'</div>';
			 }
			 
			 elseif($numberLowStock==0 and $numberOutOfStock==0){
				 echo '<div style="color:green;">In Stock | All Variations </div>';
			 }
			 
			 else{
				 echo '<div style="color:red;">Out Of Stock | '.$numberOutOfStock.' Variations</div>';

			 }
			 echo $numberLowOrOutOfStock;
    }
		else{
		$stock = $product->get_stock_quantity();
			if($stock>0 and  $stock<4){
				echo '<div style="color:orange;">Low Stock</div>';
			}
			
			elseif($stock==0){
				echo '<div style="color:red;">Out Of Stock</div>';
			}
			else{
				echo '<div style="color:green;">In Stock </div>';

			}
			
		}
	}
 
}
