/* ==========================================================================
   AUREAMED - INTERACCIONES JS PRINCIPALES
   Control de menú lateral, submenús, confirmaciones SweetAlert2 y botones
   ========================================================================== */

$(document).ready(function() {

    /* --- 1. Toggle de Submenús en el Menú Lateral --- */
    $('.nav-btn-submenu').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var $subMenu = $this.next('ul');
        var $iconChevron = $this.find('.fa-chevron-down');

        if ($subMenu.hasClass('show-nav-lateral-submenu')) {
            $subMenu.slideUp(200, function() {
                $subMenu.removeClass('show-nav-lateral-submenu');
            });
            $this.removeClass('active');
            $iconChevron.removeClass('fa-rotate-180');
        } else {
            $subMenu.slideDown(200, function() {
                $subMenu.addClass('show-nav-lateral-submenu');
            });
            $this.addClass('active');
            $iconChevron.addClass('fa-rotate-180');
        }
    });

    // Mantener abierto el submenú si contiene un enlace activo
    $('.nav-lateral-menu ul li ul').each(function() {
        if ($(this).find('a.active').length > 0) {
            $(this).addClass('show-nav-lateral-submenu').show();
            $(this).prev('.nav-btn-submenu').addClass('active').find('.fa-chevron-down').addClass('fa-rotate-180');
        }
    });

    /* --- 2. Toggle del Menú Lateral (Sidebar) --- */
    $('.show-nav-lateral').on('click', function(e) {
        e.preventDefault();
        var $navLateral = $('.nav-lateral');
        var $pageContent = $('.page-content');
        var $backdrop = $('.nav-lateral-bg');

        if ($navLateral.hasClass('active')) {
            $navLateral.removeClass('active');
            $pageContent.removeClass('active');
            $backdrop.removeClass('show');
        } else {
            $navLateral.addClass('active');
            $pageContent.addClass('active');
            $backdrop.addClass('show');
        }
    });

    // Cerrar sidebar al hacer clic en el backdrop (en móvil)
    $('.nav-lateral-bg').on('click', function() {
        $('.nav-lateral').removeClass('active');
        $('.page-content').removeClass('active');
        $(this).removeClass('show');
    });

    /* --- 3. Confirmación de Cierre de Sesión --- */
    $('.btn-exit-system').on('click', function(e) {
        e.preventDefault();
        var logoutUrl = $(this).attr('href') || 'logout.php';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: "Estás a punto de salir del sistema de AureaMed.",
                icon: 'question',
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#c84028',
                cancelButtonColor: '#dc3545',
                confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Sí, salir',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then(function(result) {
                if (result.isConfirmed || result.value === true || result.value) {
                    window.location.href = logoutUrl;
                }
            });
        } else {
            if (confirm("¿Estás seguro de cerrar sesión y salir del sistema?")) {
                window.location.href = logoutUrl;
            }
        }
    });

    /* --- 4. Tooltips y Efectos de Clic --- */
    $('[data-toggle="tooltip"]').tooltip();

});