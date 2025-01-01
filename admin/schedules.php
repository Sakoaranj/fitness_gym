<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$redirect = false;

// Handle form submission for adding/editing schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule']) || isset($_POST['edit_schedule'])) {
        $schedule_id = $_POST['schedule_id'] ?? null;
        $workout_id = $_POST['workout_id'];
        $schedule_date = $_POST['schedule_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $instructor = $_POST['instructor'];
        $max_participants = $_POST['max_participants'];
        $status = $_POST['status'] ?? 'scheduled';

        try {
            if ($schedule_id) {
                // Check if status is being changed to cancelled
                if ($status === 'cancelled') {
                    // Get members who saved this class
                    $members_stmt = $db->prepare("
                        SELECT u.id, u.email, u.full_name, w.name as workout_name
                        FROM member_preferences mp
                        JOIN users u ON mp.user_id = u.id
                        JOIN class_schedules cs ON mp.schedule_id = cs.id
                        JOIN workouts w ON cs.workout_id = w.id
                        WHERE mp.schedule_id = ? AND mp.type = 'schedule'
                    ");
                    $members_stmt->execute([$schedule_id]);
                    $affected_members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Store notification for each affected member
                    foreach ($affected_members as $member) {
                        $notification_stmt = $db->prepare("
                            INSERT INTO notifications (user_id, type, message, status, created_at)
                            VALUES (?, 'class_cancelled', ?, 'unread', NOW())
                        ");
                        $message = "Your scheduled class '{$member['workout_name']}' on " . 
                                 date('F j, Y', strtotime($schedule_date)) . " at " .
                                 date('g:i A', strtotime($start_time)) . " has been cancelled.";
                        $notification_stmt->execute([$member['id'], $message]);
                    }
                }
                
                // Update existing schedule
                $stmt = $db->prepare("
                    UPDATE class_schedules 
                    SET workout_id = ?, schedule_date = ?, start_time = ?, 
                        end_time = ?, instructor = ?, max_participants = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $workout_id, $schedule_date, $start_time, 
                    $end_time, $instructor, $max_participants, $status, $schedule_id
                ]);
                $message = "Schedule updated successfully!" . 
                          ($status === 'cancelled' ? " Affected members have been notified." : "");
            } else {
                // Add new schedule
                $stmt = $db->prepare("
                    INSERT INTO class_schedules 
                    (workout_id, schedule_date, start_time, end_time, instructor, max_participants, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $workout_id, $schedule_date, $start_time, 
                    $end_time, $instructor, $max_participants, $status
                ]);
                $message = "New schedule added successfully!";
            }
            $redirect = true;
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Get all schedules with workout details
$stmt = $db->prepare("
    SELECT cs.*, w.name as workout_name, w.difficulty_level,
           (SELECT COUNT(*) FROM member_preferences mp 
            WHERE mp.schedule_id = cs.id AND mp.type = 'schedule') as saved_count,
           (SELECT GROUP_CONCAT(u.username SEPARATOR ', ')
            FROM member_preferences mp
            JOIN users u ON mp.user_id = u.id
            WHERE mp.schedule_id = cs.id AND mp.type = 'schedule') as saved_by
    FROM class_schedules cs
    JOIN workouts w ON cs.workout_id = w.id
    ORDER BY cs.schedule_date DESC, cs.start_time ASC
");
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all workouts for the dropdown
$stmt = $db->prepare("SELECT id, name FROM workouts ORDER BY name ASC");
$stmt->execute();
$workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($message || $redirect): ?>
<script>
    <?php if ($message): ?>
    M.toast({html: '<?php echo addslashes($message); ?>', classes: 'rounded'});
    <?php endif; ?>
    
    <?php if ($redirect): ?>
    setTimeout(function() {
        window.location.href = 'schedules.php';
    }, 1000);
    <?php endif; ?>
</script>
<?php endif; ?>

<main>
    <div class="container-fluid" style="padding: 20px;">
        <div class="row">
            <!-- Add/Edit Schedule Form -->
            <div class="col s12 m4">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">event</i>
                            Add New Schedule
                        </span>
                        <form method="POST">
                            <input type="hidden" name="schedule_id" id="schedule_id">
                            
                            <div class="input-field">
                                <select name="workout_id" id="workout_id" required>
                                    <option value="" disabled selected>Choose workout</option>
                                    <?php foreach ($workouts as $workout): ?>
                                    <option value="<?php echo $workout['id']; ?>">
                                        <?php echo htmlspecialchars($workout['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Workout</label>
                            </div>

                            <div class="input-field">
                                <input type="date" id="schedule_date" name="schedule_date" required>
                                <label for="schedule_date">Date</label>
                            </div>

                            <div class="input-field">
                                <input type="time" id="start_time" name="start_time" required>
                                <label for="start_time">Start Time</label>
                            </div>

                            <div class="input-field">
                                <input type="time" id="end_time" name="end_time" required>
                                <label for="end_time">End Time</label>
                            </div>

                            <div class="input-field">
                                <input type="text" id="instructor" name="instructor" required>
                                <label for="instructor">Instructor</label>
                            </div>

                            <div class="input-field">
                                <input type="number" id="max_participants" name="max_participants" required min="1">
                                <label for="max_participants">Max Participants</label>
                            </div>

                            <div class="input-field">
                                <select name="status" id="status" required>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <label>Status</label>
                            </div>

                            <div class="input-field center-align">
                                <button type="submit" name="add_schedule" class="btn waves-effect waves-light">
                                    <i class="material-icons left">add</i>
                                    Add Schedule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Schedules List -->
            <div class="col s12 m8">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">event_note</i>
                            Class Schedules
                        </span>

                        <?php if ($schedules): ?>
                            <table class="striped responsive-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Workout</th>
                                        <th>Instructor</th>
                                        <th>Status</th>
                                        <th>Saved By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr class="<?php echo $schedule['status'] === 'cancelled' ? 'grey lighten-2' : ''; ?>">
                                            <td><?php echo date('M j, Y', strtotime($schedule['schedule_date'])); ?></td>
                                            <td>
                                                <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> -
                                                <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($schedule['workout_name']); ?>
                                                <br>
                                                <small class="grey-text"><?php echo ucfirst($schedule['difficulty_level']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($schedule['instructor']); ?></td>
                                            <td>
                                                <span class="<?php echo $schedule['status'] === 'cancelled' ? 'red-text' : 'green-text'; ?>">
                                                    <?php echo ucfirst($schedule['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($schedule['saved_count'] > 0): ?>
                                                    <span class="badge" data-badge-caption="saved">
                                                        <?php echo $schedule['saved_count']; ?>
                                                    </span>
                                                    <br>
                                                    <small class="grey-text">
                                                        <?php echo $schedule['saved_by']; ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="grey-text">No saves</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="#" class="edit-schedule btn-floating waves-effect waves-light"
                                                   data-id="<?php echo $schedule['id']; ?>"
                                                   data-workout="<?php echo $schedule['workout_id']; ?>"
                                                   data-date="<?php echo $schedule['schedule_date']; ?>"
                                                   data-start="<?php echo $schedule['start_time']; ?>"
                                                   data-end="<?php echo $schedule['end_time']; ?>"
                                                   data-instructor="<?php echo htmlspecialchars($schedule['instructor']); ?>"
                                                   data-max="<?php echo $schedule['max_participants']; ?>"
                                                   data-status="<?php echo $schedule['status']; ?>">
                                                    <i class="material-icons">edit</i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="center-align grey-text">
                                <i class="material-icons medium">event_busy</i><br>
                                No schedules found.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize select elements
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

    // Handle edit schedule
    var editButtons = document.querySelectorAll('.edit-schedule');
    editButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get data from button attributes
            var id = this.dataset.id;
            var workout = this.dataset.workout;
            var date = this.dataset.date;
            var start = this.dataset.start;
            var end = this.dataset.end;
            var instructor = this.dataset.instructor;
            var max = this.dataset.max;
            var status = this.dataset.status;
            
            // Set form values
            document.getElementById('schedule_id').value = id;
            document.getElementById('workout_id').value = workout;
            document.getElementById('schedule_date').value = date;
            document.getElementById('start_time').value = start;
            document.getElementById('end_time').value = end;
            document.getElementById('instructor').value = instructor;
            document.getElementById('max_participants').value = max;
            document.getElementById('status').value = status;
            
            // Update select dropdowns
            M.FormSelect.init(document.querySelectorAll('select'));
            
            // Update labels
            M.updateTextFields();
            
            // Update submit button
            var submitBtn = document.querySelector('.input-field.center-align button');
            submitBtn.innerHTML = '<i class="material-icons left">save</i> Update Schedule';
            submitBtn.name = 'edit_schedule';
            
            // Scroll to form
            document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
        });
    });
});
</script>

<style>
.btn-floating {
    margin: 0 5px;
}

.card .badge {
    float: none;
    margin: 2px;
}

tr.grey.lighten-2 td {
    color: #666;
}

.input-field {
    margin-bottom: 20px;
}

.card small.grey-text {
    display: block;
    margin-top: 5px;
}
</style>

<?php require_once 'includes/footer.php'; ?>
