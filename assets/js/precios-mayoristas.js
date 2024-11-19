jQuery(function($) {
    function actualizarBarra() {
        $.ajax({
            url: pm_params.ajax_url,  // Usar la URL AJAX proporcionada
            type: 'POST',
            data: { action: 'actualizar_barra_progreso' },
            success: function(response) {
                if (response.success) {
                    let totalRegular = parseFloat(response.data.total_regular);
                    let umbralMayorista = pm_params.umbralMayorista;  // Usar el umbral dinámico
                    let falta = umbralMayorista - totalRegular;
                    console.log("Umbral mayorista desde PHP:", pm_params.umbralMayorista);
                    if (totalRegular < umbralMayorista) {
                        $('.barra-mayorista').html(
                            `<p>Agrega <strong>${falta.toLocaleString('es-CO', { style: 'currency', currency: 'COP' })}</strong> más para obtener precios al por mayor.</p>
                            <div class="progreso">
                                <div class="relleno" style="width:${(totalRegular / umbralMayorista) * 100}%"></div>
                            </div>`
                        );
                    } else {
                        $('.barra-mayorista').html(
                            '<p><strong>¡Felicidades! Estás comprando con precios al por mayor.</strong></p>'
                        );
                    }
                }
            }
        });
    }

    // Escuchar eventos de actualización del carrito
    $(document.body).on('updated_cart_totals', actualizarBarra);
    actualizarBarra();
});
