<?php

// Obtener el umbral mayorista configurado
function pm_obtener_umbral_mayorista() {
    return (int) get_option( 'pm_umbral_mayorista', 100000 ); // Valor por defecto: 100000
}

// Aplicar precios mayoristas al calcular los totales del carrito
function aplicar_precios_mayoristas_al_calcular_totales( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( WC()->cart->is_empty() ) return;

    $umbral_mayorista = pm_obtener_umbral_mayorista();  // Ajusta según tu necesidad
    $subtotal = 0;

    foreach ( $cart->get_cart() as $cart_item ) {
        $subtotal += $cart_item['quantity'] * $cart_item['data']->get_price();
    }

    if ( $subtotal >= $umbral_mayorista ) {
        foreach ( $cart->get_cart() as $cart_item ) {
            $precio_mayorista = get_post_meta( $cart_item['product_id'], '_precio_mayorista', true );
            if ( ! empty( $precio_mayorista ) && $precio_mayorista > 0 ) {
                $cart_item['data']->set_price( (float) $precio_mayorista );
            }
        }
        WC()->cart->calculate_totals();
    }
}
add_action( 'woocommerce_before_calculate_totals', 'aplicar_precios_mayoristas_al_calcular_totales', 20 );

// Agregar campo de "Precio Mayorista" en la edición de producto
function agregar_campo_precio_mayorista() {
    woocommerce_wp_text_input( array(
        'id' => '_precio_mayorista',
        'label' => __( 'Precio Mayorista (COP)', 'precios-mayoristas' ),
        'data_type' => 'price',
        'description' => __( 'Introduce el precio mayorista para este producto.', 'precios-mayoristas' ),
    ));
}
add_action( 'woocommerce_product_options_pricing', 'agregar_campo_precio_mayorista' );

// Guardar el valor del precio mayorista
function guardar_precio_mayorista( $post_id ) {
    $precio_mayorista = isset( $_POST['_precio_mayorista'] ) ? sanitize_text_field( $_POST['_precio_mayorista'] ) : '';
    if ( ! empty( $precio_mayorista ) ) {
        update_post_meta( $post_id, '_precio_mayorista', esc_attr( $precio_mayorista ) );
    }
}
add_action( 'woocommerce_process_product_meta', 'guardar_precio_mayorista' );

// Calcular el total regular del carrito
function calcular_total_regular() {
    $total = 0;
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $total += $cart_item['quantity'] * $cart_item['data']->get_regular_price();
    }
    return $total;
}

// Añadir el precio mayorista después del precio regular usando jQuery
function agregar_precio_mayorista_con_jquery() {
    if ( is_product() ) { // Solo en la página de producto
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Obtener el precio mayorista desde PHP
                var precioMayorista = '<?php echo wc_price( get_post_meta( get_the_ID(), "_precio_mayorista", true ) ); ?>';

                // Si el precio mayorista no está vacío, insertarlo
                if (precioMayorista) {
                    // Insertar el precio mayorista después del precio regular
                   // $('.nasa-single-product-price').after('<p class="precio-mayorista">Precio al por mayor: <strong>' + precioMayorista + '</strong></p>');
                   $('.woocommerce-Price-amount').after('<p class="precio-mayorista">Precio  mayorista: <strong>' + precioMayorista + '</strong></p>');
                  
                }
            });
        </script>
        <?php
    }
}
add_action( 'wp_footer', 'agregar_precio_mayorista_con_jquery' );

// Mostrar barra de progreso en el carrito
function mostrar_barra_mayorista_carrito() {
    $umbral_mayorista = pm_obtener_umbral_mayorista();
    $total_regular = calcular_total_regular();

    if ( $total_regular < $umbral_mayorista ) {
        $falta = $umbral_mayorista - $total_regular;
        echo '<div class="barra-mayorista">';
        echo '<p>' . sprintf( __( 'Agrega %s más para obtener precios al por mayor.', 'precios-mayoristas' ), wc_price( $falta ) ) . '</p>';
        echo '<div class="progreso"><div class="relleno" style="width:' . ( $total_regular / $umbral_mayorista * 100 ) . '%;"></div></div>';
        echo '</div>';
    } else {
        echo '<div class="barra-mayorista"><p><strong>' . __( '¡Felicidades! Estás comprando con precios al por mayor.', 'precios-mayoristas' ) . '</strong></p></div>';
    }
}
add_action( 'woocommerce_before_cart', 'mostrar_barra_mayorista_carrito' );

// Función AJAX para actualizar la barra de progreso
function actualizar_barra_progreso_callback() {
    $total_regular = calcular_total_regular();

    wp_send_json_success(array(
        'total_regular' => $total_regular,
    ));
}
add_action( 'wp_ajax_actualizar_barra_progreso', 'actualizar_barra_progreso_callback' );
add_action( 'wp_ajax_nopriv_actualizar_barra_progreso', 'actualizar_barra_progreso_callback' );

add_action( 'wp_head', function() {
    echo '
    <style>
        .barra-mayorista { margin: 15px 0; padding: 10px; background-color: #f7f7f7; border: 1px solid #ddd; text-align: center; }
        .progreso { background-color: #e0e0e0; border-radius: 10px; height: 20px; width: 100%; margin-top: 10px; overflow: hidden; }
        .progreso .relleno { background-color: #4CAF50; height: 100%; transition: width 0.4s ease; }
    </style>';
});



/****************************************************************************************************************/
// Mostrar el total al detal y el ahorro debajo del Total del carrito, solo si se supera el umbral mayorista
function mostrar_total_al_detal() {
    // Definir el umbral mayorista
    $umbral_mayorista = pm_obtener_umbral_mayorista(); // Cambia esto al valor de tu umbral

    // Calcular el total al detal (precio regular sin precios mayoristas)
    $total_regular = calcular_total_regular();

    // Obtener el total actual del carrito con precios mayoristas aplicados
    $total_carrito = WC()->cart->get_cart_contents_total();

    // Solo mostrar si el total regular supera el umbral mayorista
    if ( $total_regular >= $umbral_mayorista ) {
        // Mostrar primero el total al detal
        echo '<tr class="cart-total-regular">';
        echo '<th>Total al detal:</th>';
        echo '<td>' . wc_price( $total_regular ) . '</td>';
        echo '</tr>';

        // Mostrar cuánto se ahorra el usuario, resaltado
        echo '<tr class="cart-total-regular ahorro-estilo">';
        echo '<th style="color: #FF5733;">Te ahorraste:</th>';
        echo '<td style="color: #FF5733;">' . wc_price( $total_regular - $total_carrito ) . '</td>';
        echo '</tr>';
    }
}
add_action( 'woocommerce_cart_totals_after_order_total', 'mostrar_total_al_detal' );

