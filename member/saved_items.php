<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get saved workouts
$stmt = $db->prepare("
    SELECT w.*, wc.name as category_name
    FROM member_preferences mp
    JOIN workouts w ON mp.workout_id = w.id
    JOIN workout_categories wc ON w.category_id = wc.id
    WHERE mp.user_id = ? AND mp.type = 'workout'
    ORDER BY w.name ASC
");
$stmt->execute([$_SESSION['user_id']]);
$saved_workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get saved class schedules
$stmt = $db->prepare("
    SELECT cs.*, w.name as workout_name, w.difficulty_level
    FROM member_preferences mp
    JOIN class_schedules cs ON mp.schedule_id = cs.id
    JOIN workouts w ON cs.workout_id = w.id
    WHERE mp.user_id = ? AND mp.type = 'schedule'
    ORDER BY cs.schedule_date ASC, cs.start_time ASC
");
$stmt->execute([$_SESSION['user_id']]);
$saved_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="container-fluid" style="padding: 20px;">
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="row" style="margin-bottom: 0;">
                            <div class="col s6">
                                <span class="card-title">
                                    <i class="material-icons left">bookmark</i>
                                    Saved Items
                                </span>
                            </div>
                            <div class="col s6 right-align">
                                <a href="workouts.php" class="btn-flat waves-effect">
                                    <i class="material-icons left">fitness_center</i>
                                    Browse Workouts
                                </a>
                                <a href="schedule.php" class="btn-flat waves-effect">
                                    <i class="material-icons left">event</i>
                                    View Schedule
                                </a>
                            </div>
                        </div>

                        <!-- Saved Workouts -->
                        <div class="section">
                            <h5>
                                <i class="material-icons left">fitness_center</i>
                                Saved Workouts
                            </h5>
                            <?php if ($saved_workouts): ?>
                                <div class="row">
                                    <?php foreach ($saved_workouts as $workout): ?>
                                        <div class="col s12 m6 l4">
                                            <div class="card workout-card hoverable">
                                                <div class="card-content">
                                                    <span class="card-title truncate">
                                                        <?php echo htmlspecialchars($workout['name']); ?>
                                                        <i class="material-icons right yellow-text text-darken-2">bookmark</i>
                                                    </span>
                                                    <p class="category">
                                                        <i class="material-icons tiny">category</i>
                                                        <?php echo htmlspecialchars($workout['category_name']); ?>
                                                    </p>
                                                    <p>
                                                        <i class="material-icons tiny">timer</i>
                                                        <?php echo $workout['duration']; ?> minutes
                                                    </p>
                                                    <p class="truncate">
                                                        <?php echo htmlspecialchars($workout['description']); ?>
                                                    </p>
                                                </div>
                                                <div class="card-action">
                                                    <div class="row" style="margin-bottom: 0;">
                                                        <div class="col s7">
                                                            <a href="#workout-modal-<?php echo $workout['id']; ?>" 
                                                               class="btn waves-effect waves-light modal-trigger">
                                                                View Details
                                                            </a>
                                                        </div>
                                                        <div class="col s5 right-align">
                                                            <form method="post" action="workouts.php">
                                                                <input type="hidden" name="workout_id" value="<?php echo $workout['id']; ?>">
                                                                <button type="submit" name="remove_workout" class="btn-floating waves-effect waves-light yellow darken-2">
                                                                    <i class="material-icons">bookmark</i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Workout Details Modal -->
                                        <div id="workout-modal-<?php echo $workout['id']; ?>" class="modal modal-fixed-footer">
                                            <div class="modal-content">
                                                <h4>
                                                    <?php echo htmlspecialchars($workout['name']); ?>
                                                    <i class="material-icons right yellow-text text-darken-2">bookmark</i>
                                                </h4>
                                                
                                                <div class="section">
                                                    <div class="badges">
                                                        <span class="new badge <?php 
                                                            echo $workout['difficulty_level'] == 'beginner' ? 'green' : 
                                                                ($workout['difficulty_level'] == 'intermediate' ? 'orange' : 'red'); 
                                                        ?>" data-badge-caption="">
                                                            <?php echo ucfirst($workout['difficulty_level']); ?>
                                                        </span>
                                                        <span class="new badge blue" data-badge-caption="minutes">
                                                            <?php echo $workout['duration']; ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="section">
                                                    <h5>Description</h5>
                                                    <p><?php echo nl2br(htmlspecialchars($workout['description'])); ?></p>
                                                </div>

                                                <?php if (!empty($workout['instructions'])): ?>
                                                    <div class="section">
                                                        <h5>Instructions</h5>
                                                        <div class="instructions">
                                                            <?php 
                                                            $instructions = explode("\n", $workout['instructions']);
                                                            echo "<ol>";
                                                            foreach ($instructions as $instruction) {
                                                                if (trim($instruction)) {
                                                                    echo "<li>" . htmlspecialchars(trim($instruction)) . "</li>";
                                                                }
                                                            }
                                                            echo "</ol>";
                                                            ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($workout['equipment'])): ?>
                                                    <div class="section">
                                                        <h5>Equipment Needed</h5>
                                                        <div class="equipment-list">
                                                            <?php 
                                                            $equipment = explode("\n", $workout['equipment']);
                                                            echo "<ul class='browser-default'>";
                                                            foreach ($equipment as $item) {
                                                                if (trim($item)) {
                                                                    echo "<li>" . htmlspecialchars(trim($item)) . "</li>";
                                                                }
                                                            }
                                                            echo "</ul>";
                                                            ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <form method="post" action="workouts.php" style="display: inline;">
                                                    <input type="hidden" name="workout_id" value="<?php echo $workout['id']; ?>">
                                                    <button type="submit" name="remove_workout" class="btn-flat waves-effect waves-light">
                                                        <i class="material-icons left">bookmark</i>
                                                        Remove from Saved
                                                    </button>
                                                </form>
                                                <a href="#!" class="modal-close waves-effect waves-light btn-flat">Close</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="center-align grey-text">
                                    <i class="material-icons medium">fitness_center</i><br>
                                    No saved workouts yet.<br>
                                    <a href="workouts.php" class="btn waves-effect waves-light" style="margin-top: 10px;">
                                        Browse Workouts
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Saved Class Schedules -->
                        <div class="section">
                            <h5>
                                <i class="material-icons left">event</i>
                                Saved Classes
                            </h5>
                            <?php if ($saved_schedules): ?>
                                <div class="row">
                                    <?php foreach ($saved_schedules as $schedule): ?>
                                        <div class="col s12 m6">
                                            <div class="card schedule-card hoverable">
                                                <div class="card-content">
                                                    <span class="card-title">
                                                        <?php echo htmlspecialchars($schedule['workout_name']); ?>
                                                        <i class="material-icons right yellow-text text-darken-2">bookmark</i>
                                                    </span>
                                                    <p>
                                                        <i class="material-icons tiny">event</i>
                                                        <?php echo date('l, F j, Y', strtotime($schedule['schedule_date'])); ?>
                                                    </p>
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
                                                </div>
                                                <div class="card-action">
                                                    <div class="row" style="margin-bottom: 0;">
                                                        <div class="col s7">
                                                            <a href="schedule.php" class="btn waves-effect waves-light">
                                                                View Schedule
                                                            </a>
                                                        </div>
                                                        <div class="col s5 right-align">
                                                            <form method="post" action="schedule.php">
                                                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                                <button type="submit" name="remove_schedule" class="btn-floating waves-effect waves-light yellow darken-2">
                                                                    <i class="material-icons">bookmark</i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="center-align grey-text">
                                    <i class="material-icons medium">event</i><br>
                                    No saved classes yet.<br>
                                    <a href="schedule.php" class="btn waves-effect waves-light" style="margin-top: 10px;">
                                        View Schedule
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
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
.section h5 {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}
.section h5 i {
    margin-right: 10px;
}
.workout-card,
.schedule-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    margin: 0.5rem 0;
}
.workout-card .card-content,
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
.category {
    color: #666;
}
.modal.modal-fixed-footer {
    max-height: 85%;
    height: 85%;
}
.badges {
    margin-bottom: 20px;
}
.badges .badge {
    margin-right: 8px;
}
.instructions,
.equipment-list {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 4px;
}
.instructions ol,
.equipment-list ul {
    margin-top: 0;
    margin-bottom: 0;
}
.instructions li,
.equipment-list li {
    margin-bottom: 5px;
}
</style>
