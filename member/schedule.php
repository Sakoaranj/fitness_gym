<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$redirect_url = '';

// Handle saving schedule preference
if (isset($_POST['save_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already saved
    $check_stmt = $db->prepare("SELECT id FROM member_preferences WHERE user_id = ? AND schedule_id = ? AND type = 'schedule'");
    $check_stmt->execute([$user_id, $schedule_id]);
    
    if (!$check_stmt->fetch()) {
        $save_stmt = $db->prepare("INSERT INTO member_preferences (user_id, schedule_id, type) VALUES (?, ?, 'schedule')");
        $save_stmt->execute([$user_id, $schedule_id]);
        $message = 'Class saved to your schedule!';
    }
    
    $redirect_url = "schedule.php";
}

// Handle removing schedule preference
if (isset($_POST['remove_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    $user_id = $_SESSION['user_id'];
    
    $remove_stmt = $db->prepare("DELETE FROM member_preferences WHERE user_id = ? AND schedule_id = ? AND type = 'schedule'");
    $remove_stmt->execute([$user_id, $schedule_id]);
    $message = 'Class removed from your schedule.';
    
    $redirect_url = "schedule.php";
}

// Get all active class schedules with workout details and saved status
$stmt = $db->prepare("
    SELECT cs.*, w.name as workout_name, w.difficulty_level, w.description,
           CASE WHEN mp.id IS NOT NULL THEN 1 ELSE 0 END as is_saved
    FROM class_schedules cs
    JOIN workouts w ON cs.workout_id = w.id
    LEFT JOIN member_preferences mp ON cs.id = mp.schedule_id 
        AND mp.user_id = ? AND mp.type = 'schedule'
    WHERE cs.schedule_date >= CURDATE()
    AND cs.status != 'cancelled'  -- Only show non-cancelled classes
    ORDER BY cs.schedule_date ASC, cs.start_time ASC
");
$stmt->execute([$_SESSION['user_id']]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group schedules by date
$grouped_schedules = [];
foreach ($schedules as $schedule) {
    $date = $schedule['schedule_date'];
    if (!isset($grouped_schedules[$date])) {
        $grouped_schedules[$date] = [];
    }
    $grouped_schedules[$date][] = $schedule;
}

// Get saved but cancelled classes for notification
$cancelled_saved_stmt = $db->prepare("
    SELECT cs.*, w.name as workout_name
    FROM class_schedules cs
    JOIN workouts w ON cs.workout_id = w.id
    JOIN member_preferences mp ON cs.id = mp.schedule_id 
    WHERE mp.user_id = ? 
    AND mp.type = 'schedule'
    AND cs.status = 'cancelled'
    AND cs.schedule_date >= CURDATE()
");
$cancelled_saved_stmt->execute([$_SESSION['user_id']]);
$cancelled_saved_classes = $cancelled_saved_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread notifications
$notifications_stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    AND status = 'unread' 
    AND type = 'class_cancelled'
    ORDER BY created_at DESC
");
$notifications_stmt->execute([$_SESSION['user_id']]);
$unread_notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($redirect_url): ?>
<script>
    <?php if ($message): ?>
    M.toast({html: '<?php echo $message; ?>', classes: 'rounded'});
    <?php endif; ?>
    
    setTimeout(function() {
        window.location.href = '<?php echo $redirect_url; ?>';
    }, 1000);
</script>
<?php endif; ?>

<main>
    <div class="container-fluid" style="padding: 20px;">
        <!-- Notifications -->
        <?php if ($unread_notifications): ?>
        <div class="row">
            <div class="col s12">
                <div class="card red lighten-4">
                    <div class="card-content">
                        <span class="card-title red-text">Class Cancellation Notices</span>
                        <?php foreach ($unread_notifications as $notification): ?>
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cancelled Classes Notification -->
        <?php if ($cancelled_saved_classes): ?>
        <div class="row">
            <div class="col s12">
                <div class="card orange lighten-4">
                    <div class="card-content">
                        <span class="card-title orange-text text-darken-4">Cancelled Classes in Your Schedule</span>
                        <p>The following saved classes have been cancelled:</p>
                        <ul>
                            <?php foreach ($cancelled_saved_classes as $class): ?>
                            <li>
                                • <?php echo htmlspecialchars($class['workout_name']); ?> on 
                                <?php echo date('F j, Y', strtotime($class['schedule_date'])); ?> at
                                <?php echo date('g:i A', strtotime($class['start_time'])); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="grey-text">These classes will be automatically removed from your schedule.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="row" style="margin-bottom: 0;">
                            <div class="col s6">
                                <span class="card-title">
                                    <i class="material-icons left">event</i>
                                    Class Schedule
                                </span>
                            </div>
                            <div class="col s6 right-align">
                                <a href="saved_items.php" class="btn-flat waves-effect">
                                    <i class="material-icons left">bookmark</i>
                                    Saved Items
                                </a>
                            </div>
                        </div>

                        <?php if ($grouped_schedules): ?>
                            <?php foreach ($grouped_schedules as $date => $day_schedules): ?>
                                <div class="section">
                                    <h5 class="date-header">
                                        <?php echo date('l, F j, Y', strtotime($date)); ?>
                                    </h5>
                                    <div class="row">
                                        <?php foreach ($day_schedules as $schedule): ?>
                                            <div class="col s12 m6">
                                                <div class="card schedule-card hoverable">
                                                    <div class="card-content">
                                                        <span class="card-title">
                                                            <?php echo htmlspecialchars($schedule['workout_name']); ?>
                                                            <?php if ($schedule['is_saved']): ?>
                                                                <i class="material-icons right yellow-text text-darken-2">bookmark</i>
                                                            <?php endif; ?>
                                                        </span>
                                                        <p>
                                                            <i class="material-icons tiny">access_time</i>
                                                            <?php 
                                                            echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                                 date('g:i A', strtotime($schedule['end_time'])); 
                                                            ?>
                                                        </p>
                                                        <p>
                                                            <i class="material-icons tiny">person</i>
                                                            <?php echo htmlspecialchars($schedule['instructor']); ?>
                                                        </p>
                                                        <p>
                                                            <i class="material-icons tiny">fitness_center</i>
                                                            <?php echo ucfirst($schedule['difficulty_level']); ?> Level
                                                        </p>
                                                        <p>
                                                            <i class="material-icons tiny">group</i>
                                                            <?php echo $schedule['max_participants']; ?> max participants
                                                        </p>
                                                        <?php if (!empty($schedule['description'])): ?>
                                                            <p class="truncate">
                                                                <?php echo htmlspecialchars($schedule['description']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="card-action">
                                                        <div class="row" style="margin-bottom: 0;">
                                                            <div class="col s7">
                                                                <a href="#schedule-modal-<?php echo $schedule['id']; ?>" 
                                                                   class="btn waves-effect waves-light modal-trigger">
                                                                    View Details
                                                                </a>
                                                            </div>
                                                            <div class="col s5 right-align">
                                                                <form method="post">
                                                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                                    <?php if ($schedule['is_saved']): ?>
                                                                        <button type="submit" name="remove_schedule" 
                                                                                class="btn-floating waves-effect waves-light yellow darken-2"
                                                                                title="Remove from saved">
                                                                            <i class="material-icons">bookmark</i>
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <button type="submit" name="save_schedule" 
                                                                                class="btn-floating waves-effect waves-light"
                                                                                title="Save to your schedule">
                                                                            <i class="material-icons">bookmark_border</i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Schedule Details Modal -->
                                            <div id="schedule-modal-<?php echo $schedule['id']; ?>" class="modal">
                                                <div class="modal-content">
                                                    <h4>
                                                        <?php echo htmlspecialchars($schedule['workout_name']); ?>
                                                        <?php if ($schedule['is_saved']): ?>
                                                            <i class="material-icons right yellow-text text-darken-2">bookmark</i>
                                                        <?php endif; ?>
                                                    </h4>
                                                    
                                                    <div class="section">
                                                        <div class="badges">
                                                            <span class="new badge <?php 
                                                                echo $schedule['difficulty_level'] == 'beginner' ? 'green' : 
                                                                    ($schedule['difficulty_level'] == 'intermediate' ? 'orange' : 'red'); 
                                                            ?>" data-badge-caption="">
                                                                <?php echo ucfirst($schedule['difficulty_level']); ?>
                                                            </span>
                                                            <span class="new badge blue" data-badge-caption="">
                                                                <?php echo date('l, F j', strtotime($schedule['schedule_date'])); ?>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="section">
                                                        <h5>Class Details</h5>
                                                        <p>
                                                            <strong>Time:</strong> 
                                                            <?php 
                                                            echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                                 date('g:i A', strtotime($schedule['end_time'])); 
                                                            ?>
                                                        </p>
                                                        <p>
                                                            <strong>Instructor:</strong> 
                                                            <?php echo htmlspecialchars($schedule['instructor']); ?>
                                                        </p>
                                                        <p>
                                                            <strong>Maximum Participants:</strong> 
                                                            <?php echo $schedule['max_participants']; ?>
                                                        </p>
                                                    </div>

                                                    <?php if (!empty($schedule['description'])): ?>
                                                        <div class="section">
                                                            <h5>Description</h5>
                                                            <p><?php echo nl2br(htmlspecialchars($schedule['description'])); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                        <?php if ($schedule['is_saved']): ?>
                                                            <button type="submit" name="remove_schedule" class="btn-flat waves-effect waves-light">
                                                                <i class="material-icons left">bookmark</i>
                                                                Remove from Saved
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" name="save_schedule" class="btn-flat waves-effect waves-light">
                                                                <i class="material-icons left">bookmark_border</i>
                                                                Save to Schedule
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                    <a href="#!" class="modal-close waves-effect waves-light btn-flat">Close</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="center-align grey-text">
                                <i class="material-icons medium">event_busy</i><br>
                                No upcoming classes scheduled.
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
    // Initialize modals
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals, {
        dismissible: true,
        inDuration: 300,
        outDuration: 200
    });
});
</script>

<style>
.container-fluid {
    width: 95%;
    margin: 0 auto;
}
.section {
    margin-top: 30px;
}
.date-header {
    color: #26a69a;
    font-size: 1.5rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}
.schedule-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    margin: 0.5rem 0;
}
.schedule-card .card-content {
    flex-grow: 1;
}
.card-title {
    font-size: 1.4rem !important;
    margin-bottom: 10px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}
.card-title i {
    font-size: 1.4rem;
}
.card p {
    margin: 8px 0;
    display: flex;
    align-items: center;
}
.card i.tiny {
    margin-right: 8px;
}
.badges {
    margin-bottom: 20px;
}
.badges .badge {
    margin-right: 8px;
}
.modal .section {
    margin-top: 20px;
}
.modal .section h5 {
    color: #26a69a;
    margin-bottom: 15px;
}
</style>
<?php require_once 'includes/footer.php'; ?>
