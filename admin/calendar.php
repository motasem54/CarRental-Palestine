<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get all rentals for calendar
$stmt = $db->query("
    SELECT r.*, 
           c.full_name as customer_name,
           ca.brand, ca.model, ca.plate_number
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars ca ON r.car_id = ca.id
    WHERE r.status IN ('confirmed', 'active')
");
$rentals = $stmt->fetchAll();

// Prepare events for FullCalendar
$events = [];
foreach ($rentals as $rental) {
    $color = $rental['status'] === 'active' ? '#4CAF50' : '#2196F3';
    $events[] = [
        'id' => $rental['id'],
        'title' => $rental['customer_name'] . ' - ' . $rental['brand'] . ' ' . $rental['model'],
        'start' => $rental['start_date'],
        'end' => $rental['end_date'],
        'color' => $color,
        'extendedProps' => [
            'rental_number' => $rental['rental_number'],
            'plate_number' => $rental['plate_number'],
            'phone' => $rental['customer_name']
        ]
    ];
}

$page_title = 'تقويم الحجوزات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- FullCalendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-calendar-alt me-2"></i>تقويم الحجوزات</h5>
            <p>عرض جميع الحجوزات في التقويم</p>
        </div>
        <div class="top-bar-right">
            <a href="rental_add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>حجز جديد
            </a>
        </div>
    </div>

    <!-- Legend -->
    <div class="mb-3">
        <span class="badge bg-success me-2">■ نشطة</span>
        <span class="badge bg-info me-2">■ مؤكدة</span>
    </div>

    <div class="table-container">
        <div id="calendar"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'ar',
        direction: 'rtl',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: <?php echo json_encode($events); ?>,
        eventClick: function(info) {
            alert('رقم الحجز: ' + info.event.extendedProps.rental_number + '\n' +
                  'العميل: ' + info.event.title + '\n' +
                  'الفترة: ' + info.event.start.toLocaleDateString('ar') + ' - ' + 
                  info.event.end.toLocaleDateString('ar'));
        },
        height: 'auto'
    });
    calendar.render();
});
</script>

<?php include 'includes/footer.php'; ?>