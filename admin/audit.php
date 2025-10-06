<?php
/**
 * Admin Audit Log Page
 * Displays all admin activities
 */

// Include admin authentication
require_once 'includes/admin_auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: adminlogin.php');
    exit();
}

// Validate admin session
if (!validateAdminSession()) {
    header('Location: adminlogin.php');
    exit();
}

// Include audit logger
require_once 'includes/audit_logger.php';

// Log page access
auditPageView('audit.php');

// Get current admin user info
$currentUser = getCurrentAdminUser();

// Database connection
require_once '../db.php';

// Get pagination parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25; // Fixed limit
$offset = ($page - 1) * $limit;

try {
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM admin_audit_log";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Get audit records
    $query = "
        SELECT * FROM admin_audit_log
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $auditRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Audit page error: " . $e->getMessage());
    $auditRecords = [];
    $totalRecords = 0;
    $totalPages = 0;
}

$pageTitle = "Audit Log";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ShoeStore Admin</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">

    <!-- Custom Audit CSS -->
    <style>
        .audit-table {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .audit-record {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .audit-record:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .audit-table th {
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 1rem 0.75rem;
            white-space: nowrap;
        }

        .audit-table td {
            padding: 0.75rem;
            vertical-align: top;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .audit-table .table-responsive {
            border-radius: 15px;
        }

        .severity-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .severity-low { background-color: #28a745; color: #fff; }
        .severity-medium { background-color: #ffc107; color: #000; }
        .severity-high { background-color: #fd7e14; color: #fff; }
        .severity-critical { background-color: #dc3545; color: #fff; }

        .status-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-success { background-color: #28a745; color: #fff; }
        .status-failed { background-color: #dc3545; color: #fff; }
        .status-warning { background-color: #ffc107; color: #000; }

        .action-type-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            background-color: #6c757d;
            color: #fff;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .audit-details {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.4;
            max-width: 300px;
            word-wrap: break-word;
        }

        .audit-admin-info {
            min-width: 150px;
        }

        .audit-date-info {
            min-width: 120px;
        }

        .audit-ip {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .pagination-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-top: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .audit-table th,
            .audit-table td {
                padding: 0.5rem 0.4rem;
                font-size: 0.85rem;
            }

            .audit-details {
                max-width: 200px;
                font-size: 0.8rem;
            }

            .header-title h1 {
                font-size: 1.5rem;
            }

            .header-subtitle {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 768px) {
            .stats-summary {
                display: none;
            }

            .audit-table th,
            .audit-table td {
                padding: 0.4rem 0.3rem;
                font-size: 0.8rem;
            }

            .audit-details {
                max-width: 150px;
                font-size: 0.75rem;
            }

            .severity-badge,
            .status-badge,
            .action-type-badge {
                font-size: 0.6rem;
                padding: 0.2rem 0.4rem;
            }
        }

        @media (max-width: 576px) {
            .pagination-container {
                padding: 15px;
            }

            .audit-table th:nth-child(6),
            .audit-table td:nth-child(6),
            .audit-table th:nth-child(9),
            .audit-table td:nth-child(9) {
                display: none;
            }

            .audit-table th,
            .audit-table td {
                padding: 0.3rem 0.2rem;
                font-size: 0.75rem;
            }

            .audit-details {
                max-width: 120px;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 480px) {
            .audit-table th:nth-child(4),
            .audit-table td:nth-child(4),
            .audit-table th:nth-child(8),
            .audit-table td:nth-child(8) {
                display: none;
            }
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1><i class="fas fa-clipboard-list me-2"></i><?php echo $pageTitle; ?></h1>
                        <p class="header-subtitle">Monitor all administrative activities and system events</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="stats-summary">
                        <div class="stat-item">
                            <small>Total Records</small>
                            <strong><?php echo number_format($totalRecords); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Content -->
            <div class="admin-content-wrapper">
                <div class="content-container">
                    <!-- Audit Records Table -->
            <div class="audit-table">
                <?php if (empty($auditRecords)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x mb-3" style="color: rgba(255, 255, 255, 0.3);"></i>
                        <h5>No audit records found</h5>
                        <p class="text-muted">No admin activities have been logged yet. Check back later.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0" style="min-width: 800px;">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Target</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditRecords as $record): ?>
                                    <tr class="audit-record">
                                        <td class="audit-date-info">
                                            <div class="fw-bold"><?php echo date('M j, Y', strtotime($record['created_at'])); ?></div>
                                            <small class="text-muted"><?php echo date('H:i:s', strtotime($record['created_at'])); ?></small>
                                        </td>
                                        <td class="audit-admin-info">
                                            <div class="fw-bold"><?php echo htmlspecialchars($record['admin_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($record['admin_email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge action-type-badge">
                                                <?php echo ucfirst($record['action_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-capitalize fw-bold"><?php echo htmlspecialchars($record['action_category']); ?></span>
                                        </td>
                                        <td>
                                            <div class="audit-details">
                                                <?php echo htmlspecialchars($record['action_description']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($record['target_type']): ?>
                                                <div class="text-capitalize fw-bold"><?php echo htmlspecialchars($record['target_type']); ?></div>
                                                <?php if ($record['target_id']): ?>
                                                    <small class="text-muted">#<?php echo htmlspecialchars($record['target_id']); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge severity-badge severity-<?php echo $record['severity']; ?>">
                                                <?php echo ucfirst($record['severity']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-<?php echo $record['status']; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="audit-ip"><?php echo htmlspecialchars($record['ip_address']); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="pagination-info">
                            <span class="text-muted">
                                Showing <?php echo number_format($offset + 1); ?> to
                                <?php echo number_format(min($offset + $limit, $totalRecords)); ?> of
                                <?php echo number_format($totalRecords); ?> records
                            </span>
                        </div>

                        <nav aria-label="Audit log pagination">
                            <ul class="pagination pagination-dark mb-0">
                                <!-- Previous Page -->
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                                    </li>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $totalPages; ?>">
                                            <?php echo $totalPages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Next Page -->
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // Auto-refresh page every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
