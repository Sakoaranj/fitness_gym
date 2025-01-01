<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $name = $_POST['name'] ?? '';
            $category_id = $_POST['category_id'] ?? '';
            $description = $_POST['description'] ?? '';
            $difficulty_level = $_POST['difficulty_level'] ?? '';
            $duration = $_POST['duration'] ?? '';

            if ($name && $category_id && $description && $difficulty_level && $duration) {
                $stmt = $db->prepare("INSERT INTO workouts (name, category_id, description, difficulty_level, duration) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $category_id, $description, $difficulty_level, $duration])) {
                    $success_message = "Workout added successfully.";
                } else {
                    $error_message = "Error adding workout.";
                }
            }
            break;

        case 'edit':
            $workout_id = $_POST['workout_id'] ?? '';
            $name = $_POST['name'] ?? '';
            $category_id = $_POST['category_id'] ?? '';
            $description = $_POST['description'] ?? '';
            $difficulty_level = $_POST['difficulty_level'] ?? '';
            $duration = $_POST['duration'] ?? '';

            if ($workout_id && $name && $category_id && $description && $difficulty_level && $duration) {
                $stmt = $db->prepare("UPDATE workouts SET name = ?, category_id = ?, description = ?, difficulty_level = ?, duration = ? WHERE id = ?");
                if ($stmt->execute([$name, $category_id, $description, $difficulty_level, $duration, $workout_id])) {
                    $success_message = "Workout updated successfully.";
                } else {
                    $error_message = "Error updating workout.";
                }
            }
            break;

        case 'delete':
            $workout_id = $_POST['workout_id'] ?? '';
            if ($workout_id) {
                $stmt = $db->prepare("DELETE FROM workouts WHERE id = ?");
                if ($stmt->execute([$workout_id])) {
                    $success_message = "Workout deleted successfully.";
                } else {
                    $error_message = "Error deleting workout.";
                }
            }
            break;
    }
}

// Get all workout categories
$query = "SELECT * FROM workout_categories ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all workouts with their categories
$query = "
    SELECT w.*, wc.name as category_name 
    FROM workouts w
    JOIN workout_categories wc ON w.category_id = wc.id
    ORDER BY w.created_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute();
$workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="dashboard-container">
        <?php if ($success_message): ?>
            <div class="card-panel green white-text success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="card-panel red white-text error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="row" style="margin-bottom: 0;">
                            <div class="col s6">
                                <span class="card-title">Workouts</span>
                            </div>
                            <div class="col s6 right-align">
                                <a href="#add-workout-modal" class="btn blue waves-effect waves-light modal-trigger">
                                    <i class="material-icons left">add</i>Add Workout
                                </a>
                            </div>
                        </div>
                        
                        <div class="row" style="margin-top: 20px;">
                            <div class="col s12">
                                <table class="striped responsive-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th>Difficulty</th>
                                            <th>Duration</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($workouts as $workout): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($workout['name']); ?></td>
                                                <td><?php echo htmlspecialchars($workout['category_name']); ?></td>
                                                <td><?php echo htmlspecialchars($workout['description']); ?></td>
                                                <td>
                                                    <span class="new badge <?php 
                                                        echo $workout['difficulty_level'] === 'beginner' ? 'green' : 
                                                            ($workout['difficulty_level'] === 'intermediate' ? 'orange' : 'red'); 
                                                    ?>" data-badge-caption="">
                                                        <?php echo ucfirst($workout['difficulty_level']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $workout['duration']; ?> mins</td>
                                                <td>
                                                    <a href="#edit-workout-modal" 
                                                       class="btn-floating btn-small waves-effect waves-light blue modal-trigger edit-workout"
                                                       data-id="<?php echo $workout['id']; ?>"
                                                       data-name="<?php echo htmlspecialchars($workout['name']); ?>"
                                                       data-category="<?php echo $workout['category_id']; ?>"
                                                       data-description="<?php echo htmlspecialchars($workout['description']); ?>"
                                                       data-difficulty="<?php echo $workout['difficulty_level']; ?>"
                                                       data-duration="<?php echo $workout['duration']; ?>">
                                                        <i class="material-icons">edit</i>
                                                    </a>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="workout_id" value="<?php echo $workout['id']; ?>">
                                                        <button type="submit" name="action" value="delete" 
                                                                class="btn-floating btn-small waves-effect waves-light red"
                                                                onclick="return confirm('Are you sure you want to delete this workout?');">
                                                            <i class="material-icons">delete</i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Workout Modal -->
    <div id="add-workout-modal" class="modal">
        <div class="modal-content">
            <h4>Add New Workout</h4>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="input-field col s12">
                        <input id="name" type="text" name="name" required>
                        <label for="name">Workout Name</label>
                    </div>
                    <div class="input-field col s12">
                        <select name="category_id" required>
                            <option value="" disabled selected>Choose category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Category</label>
                    </div>
                    <div class="input-field col s12">
                        <textarea id="description" name="description" class="materialize-textarea" required></textarea>
                        <label for="description">Description</label>
                    </div>
                    <div class="input-field col s12">
                        <select name="difficulty_level" required>
                            <option value="" disabled selected>Choose difficulty</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                        <label>Difficulty Level</label>
                    </div>
                    <div class="input-field col s12">
                        <input id="duration" type="number" name="duration" min="1" required>
                        <label for="duration">Duration (minutes)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
                    <button type="submit" class="waves-effect waves-green btn blue">Add Workout</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Workout Modal -->
    <div id="edit-workout-modal" class="modal">
        <div class="modal-content">
            <h4>Edit Workout</h4>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="workout_id" id="edit-workout-id">
                <div class="row">
                    <div class="input-field col s12">
                        <input id="edit-name" type="text" name="name" required>
                        <label for="edit-name">Workout Name</label>
                    </div>
                    <div class="input-field col s12">
                        <select name="category_id" id="edit-category" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Category</label>
                    </div>
                    <div class="input-field col s12">
                        <textarea id="edit-description" name="description" class="materialize-textarea" required></textarea>
                        <label for="edit-description">Description</label>
                    </div>
                    <div class="input-field col s12">
                        <select name="difficulty_level" id="edit-difficulty" required>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                        <label>Difficulty Level</label>
                    </div>
                    <div class="input-field col s12">
                        <input id="edit-duration" type="number" name="duration" min="1" required>
                        <label for="edit-duration">Duration (minutes)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
                    <button type="submit" class="waves-effect waves-green btn blue">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    // Initialize select inputs
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

    // Initialize textareas
    var textareas = document.querySelectorAll('.materialize-textarea');
    M.textareaAutoResize(textareas);

    // Edit workout modal
    document.querySelectorAll('.edit-workout').forEach(function(button) {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var name = this.getAttribute('data-name');
            var category = this.getAttribute('data-category');
            var description = this.getAttribute('data-description');
            var difficulty = this.getAttribute('data-difficulty');
            var duration = this.getAttribute('data-duration');

            document.getElementById('edit-workout-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-category').value = category;
            document.getElementById('edit-description').value = description;
            document.getElementById('edit-difficulty').value = difficulty;
            document.getElementById('edit-duration').value = duration;

            // Update labels for materialize
            M.updateTextFields();
            
            // Reinitialize select inputs after setting values
            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);
            
            // Reinitialize textareas
            var textareas = document.querySelectorAll('.materialize-textarea');
            M.textareaAutoResize(textareas);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
