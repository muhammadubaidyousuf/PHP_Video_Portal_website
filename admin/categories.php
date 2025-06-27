<?php
// Include session
require_once '../includes/session.php';

// Handle category actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $slug = create_slug($name);
        
        // Check if category exists
        $query = "SELECT id FROM categories WHERE name = ? OR slug = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $name, $slug);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            $query = "INSERT INTO categories (name, slug) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $name, $slug);
            
            if ($stmt->execute()) {
                set_message('Category added successfully!');
            } else {
                set_message('Error adding category!', 'danger');
            }
        } else {
            set_message('Category already exists!', 'danger');
        }
    } 
    elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name']);
        $slug = create_slug($name);
        
        // Check if category exists
        $query = "SELECT id FROM categories WHERE (name = ? OR slug = ?) AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $name, $slug, $id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            $query = "UPDATE categories SET name = ?, slug = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $name, $slug, $id);
            
            if ($stmt->execute()) {
                set_message('Category updated successfully!');
            } else {
                set_message('Error updating category!', 'danger');
            }
        } else {
            set_message('Category already exists!', 'danger');
        }
    }
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Check if category is being used
        $query = "SELECT id FROM videos WHERE category_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            $query = "DELETE FROM categories WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                set_message('Category deleted successfully!');
            } else {
                set_message('Error deleting category!', 'danger');
            }
        } else {
            set_message('Cannot delete category. It is being used by videos!', 'danger');
        }
    }
    
    // Redirect to avoid form resubmission
    header('Location: categories.php');
    exit();
}

// Get all categories
$query = "SELECT c.*, COUNT(v.id) as video_count 
          FROM categories c 
          LEFT JOIN videos v ON c.id = v.category_id 
          GROUP BY c.id 
          ORDER BY c.name ASC";
$categories = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <title>Categories - OMGTube</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #4e73df;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <?php include '../includes/layout/admin-sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tags me-2"></i>Categories</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus-circle me-1"></i> Add Category
                    </button>
                </div>
                
                <?php display_message(); ?>
                
                <!-- Categories List -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Videos</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($categories->num_rows > 0): ?>
                                        <?php while ($category = $categories->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $category['name']; ?></td>
                                                <td><?php echo $category['slug']; ?></td>
                                                <td><?php echo $category['video_count']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info edit-category" 
                                                            data-id="<?php echo $category['id']; ?>"
                                                            data-name="<?php echo $category['name']; ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editCategoryModal">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($category['video_count'] == 0): ?>
                                                        <button class="btn btn-sm btn-danger delete-category"
                                                                data-id="<?php echo $category['id']; ?>"
                                                                data-name="<?php echo $category['name']; ?>"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteCategoryModal">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No categories found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="categories.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="categories.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="categories.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Are you sure you want to delete the category "<span id="delete_name"></span>"?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Handle edit category
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
            });
        });
        
        // Handle delete category
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('delete_id').value = this.dataset.id;
                document.getElementById('delete_name').textContent = this.dataset.name;
            });
        });
    </script>
</body>
</html>
