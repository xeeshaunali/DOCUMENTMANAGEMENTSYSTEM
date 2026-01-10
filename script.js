$(document).ready(function () {
    // Pagination and Search
    $('#myTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 10
    });

    // Search functionality
    $('#search').on('keyup', function () {
        $('#myTable').DataTable().search($(this).val()).draw();
    });
});