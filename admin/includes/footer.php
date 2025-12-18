</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>

<script>
$(document).ready(function() {
    // DataTables Arabic
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/ar.json'
            },
            order: [[0, 'desc']]
        });
    }

    // Select2 Arabic
    if ($.fn.select2) {
        $('.select2').select2({
            language: 'ar',
            dir: 'rtl'
        });
    }

    // Mobile sidebar toggle
    $('#sidebarToggle').click(function() {
        $('#sidebar').toggleClass('show');
    });
});
</script>

</body>
</html>