<?php
/**
 * Plugin Name: Precios Mayoristas
 * Description: Aplica precios mayoristas cuando el carrito alcanza un subtotal específico.
 * Version: 1.9
 * Author:  Eder Alvarez
 * Text Domain: precios-mayoristas
 * Domain Path: /languages
 */

// Evitar acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) exit;

// Incluir el archivo de funciones
require_once plugin_dir_path( __FILE__ ) . 'includes/funciones.php';

// Encolar scripts y estilos
function pm_encolar_scripts() {
    wp_enqueue_script(
        'precios-mayoristas',
        plugin_dir_url( __FILE__ ) . 'assets/js/precios-mayoristas.js',
        array( 'jquery' ),
        '1.0',
        true
    );

       // Verificar si el valor del umbral se pasa desde la configuración
       $umbral = (int) get_option( 'pm_umbral_mayorista', 100000 );
         error_log(" umbral ".$umbral);
       // Pasar el valor al script
       wp_localize_script( 'precios-mayoristas', 'pm_params', array(
           'ajax_url' => admin_url( 'admin-ajax.php' ),
           'umbralMayorista' => $umbral,
       ));
}
add_action( 'wp_enqueue_scripts', 'pm_encolar_scripts' );

// Registrar el menú en el panel de administración
function pm_registrar_menu() {
    add_menu_page(
        __( 'Precios Mayoristas', 'precios-mayoristas' ),
        __( 'Precios Mayoristas', 'precios-mayoristas' ),
        'manage_options',
        'precios-mayoristas',
        'pm_mostrar_configuracion',
        'dashicons-cart',
        56
    );
}
add_action( 'admin_menu', 'pm_registrar_menu' );

// Mostrar la interfaz de configuración
function pm_mostrar_configuracion() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'Configuración de Precios Mayoristas', 'precios-mayoristas' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'pm_configuracion_grupo' );
            do_settings_sections( 'precios-mayoristas' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registrar configuración
function pm_registrar_configuracion() {
    register_setting( 'pm_configuracion_grupo', 'pm_umbral_mayorista' );

    add_settings_section(
        'pm_seccion_general',
        __( 'Ajustes Generales', 'precios-mayoristas' ),
        null,
        'precios-mayoristas'
    );

    add_settings_field(
        'pm_umbral_mayorista',
        __( 'Umbral Mayorista', 'precios-mayoristas' ),
        'pm_campo_umbral_mayorista',
        'precios-mayoristas',
        'pm_seccion_general'
    );
}
add_action( 'admin_init', 'pm_registrar_configuracion' );

// Campo del umbral mayorista
function pm_campo_umbral_mayorista() {
    $umbral = get_option( 'pm_umbral_mayorista', 100000 ); // Valor por defecto
    echo '<input type="number" name="pm_umbral_mayorista" value="' . esc_attr( $umbral ) . '" class="regular-text" />';
}


