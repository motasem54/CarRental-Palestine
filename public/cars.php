<?php
require_once '../config/settings.php';
$db = Database::getInstance()->getConnection();

// Get filters
$type = $_GET['type'] ?? '';
$transmission = $_GET['transmission'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM cars WHERE status = 'available'";
$params = [];

if ($type) {
    $sql .= " AND type = ?";
    $params[] = $type;
}

if ($transmission) {
    $sql .= " AND transmission = ?";
    $params[] = $transmission;
}

if ($search) {
    $sql .= " AND (brand LIKE ? OR model LIKE ? OR plate_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll();
} catch (Exception $e) {
    $cars = [];
}

$page_title = 'السيارات المتاحة - ' . SITE_NAME;
include 'includes/header.php';
?>

<style>
.filter-section {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.car-card {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.4s;
    height: 100%;
    background: white;
}

.car-card:hover {
    transform: translateY(-15px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
}

.car-image {
    position: relative;
    overflow: hidden;
    height: 280px;
    background: #f8f9fa;
}

.car-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: 0.4s;
}

.car-card:hover .car-image img {
    transform: scale(1.1);
}

.car-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 8px 20px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.9rem;
    box-shadow: 0 4px 15px rgba(255, 87, 34, 0.4);
}

.car-card .card-body {
    padding: 25px;
}

.car-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--dark);
    margin-bottom: 15px;
}

.car-specs {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.car-spec {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 0.95rem;
}

.car-spec i {
    color: var(--primary);
}

.car-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 2px solid #f8f9fa;
}

.car-price {
    font-size: 2rem;
    font-weight: 900;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.car-price small {
    font-size: 1rem;
    color: #666;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container text-center">
        <h1>🚗 السيارات المتاحة</h1>
        <p class="lead" style="color: rgba(255,255,255,0.9);">اختر سيارتك المثالية من بين أسطولنا الحديث</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                <li class="breadcrumb-item active">السيارات</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container my-5">
    <!-- Filters -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">بحث</label>
                <input type="text" name="search" class="form-control" placeholder="اسم السيارة..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">نوع السيارة</label>
                <select name="type" class="form-control">
                    <option value="">الكل</option>
                    <?php foreach (CAR_TYPES as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?php echo $type === $key ? 'selected' : ''; ?>>
                        <?php echo $value; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">نوع الناقل</label>
                <select name="transmission" class="form-control">
                    <option value="">الكل</option>
                    <?php foreach (TRANSMISSION_TYPES as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?php echo $transmission === $key ? 'selected' : ''; ?>>
                        <?php echo $value; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>بحث
                </button>
            </div>
        </form>
    </div>

    <!-- Results Info -->
    <div class="mb-4">
        <h5>تم العثور على <?php echo count($cars); ?> سيارة</h5>
    </div>

    <!-- Cars Grid -->
    <div class="row g-4">
        <?php if (count($cars) > 0): ?>
            <?php foreach ($cars as $car): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card car-card">
                    <div class="car-image">
                        <img src="<?php echo UPLOADS_URL . '/cars/' . $car['image']; ?>" 
                             alt="<?php echo $car['brand'] . ' ' . $car['model']; ?>"
                             onerror="this.src='<?php echo UPLOADS_URL; ?>/cars/no_image.jpg'">
                        <div class="car-badge"><?php echo CAR_TYPES[$car['type']]; ?></div>
                    </div>
                    <div class="card-body">
                        <h5 class="car-title"><?php echo $car['brand'] . ' ' . $car['model'] . ' ' . $car['year']; ?></h5>
                        <div class="car-specs">
                            <div class="car-spec">
                                <i class="fas fa-users"></i>
                                <span><?php echo $car['seats']; ?> مقاعد</span>
                            </div>
                            <div class="car-spec">
                                <i class="fas fa-cog"></i>
                                <span><?php echo TRANSMISSION_TYPES[$car['transmission']]; ?></span>
                            </div>
                            <div class="car-spec">
                                <i class="fas fa-gas-pump"></i>
                                <span><?php echo FUEL_TYPES[$car['fuel_type']]; ?></span>
                            </div>
                            <div class="car-spec">
                                <i class="fas fa-palette"></i>
                                <span><?php echo $car['color']; ?></span>
                            </div>
                        </div>
                        <div class="car-footer">
                            <div class="car-price">
                                <?php echo formatCurrency($car['daily_rate']); ?>
                                <small>/يوم</small>
                            </div>
                            <a href="car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary btn-sm">
                                التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-car fa-5x text-muted mb-4"></i>
                <h4 class="text-muted">لم يتم العثور على سيارات</h4>
                <p>جرّب تغيير فلاتر البحث</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>