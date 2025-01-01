<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Check subscription status
$subscription_stmt = $db->prepare("
    SELECT status
    FROM subscriptions 
    WHERE user_id = ? 
    AND status IN ('active', 'pending')
    ORDER BY created_at DESC 
    LIMIT 1
");
$subscription_stmt->execute([$_SESSION['user_id']]);
$subscription = $subscription_stmt->fetch(PDO::FETCH_ASSOC);

// Get selected difficulty level from URL
$selected_difficulty = isset($_GET['level']) ? $_GET['level'] : null;

$redirect_url = '';
$message = '';

// Handle saving workout preference
if (isset($_POST['save_workout'])) {
    $workout_id = $_POST['workout_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already saved
    $check_stmt = $db->prepare("SELECT id FROM member_preferences WHERE user_id = ? AND workout_id = ? AND type = 'workout'");
    $check_stmt->execute([$user_id, $workout_id]);
    
    if (!$check_stmt->fetch()) {
        $save_stmt = $db->prepare("INSERT INTO member_preferences (user_id, workout_id, type) VALUES (?, ?, 'workout')");
        $save_stmt->execute([$user_id, $workout_id]);
        $message = 'Workout saved successfully!';
    }
    
    $redirect_url = "workouts.php" . ($selected_difficulty ? "?level=" . $selected_difficulty : "");
}

// Handle removing workout preference
if (isset($_POST['remove_workout'])) {
    $workout_id = $_POST['workout_id'];
    $user_id = $_SESSION['user_id'];
    
    $remove_stmt = $db->prepare("DELETE FROM member_preferences WHERE user_id = ? AND workout_id = ? AND type = 'workout'");
    $remove_stmt->execute([$user_id, $workout_id]);
    $message = 'Workout removed from saved items.';
    
    $redirect_url = "workouts.php" . ($selected_difficulty ? "?level=" . $selected_difficulty : "");
}

// Get all workouts with their categories and saved status
$stmt = $db->prepare("
    SELECT w.*, wc.name as category_name,
           CASE WHEN mp.id IS NOT NULL THEN 1 ELSE 0 END as is_saved
    FROM workouts w
    JOIN workout_categories wc ON w.category_id = wc.id
    LEFT JOIN member_preferences mp ON w.id = mp.workout_id 
        AND mp.user_id = ? AND mp.type = 'workout'
    WHERE w.difficulty_level IN ('beginner', 'intermediate', 'advanced')
    " . ($selected_difficulty ? "AND w.difficulty_level = ?" : "") . "
    ORDER BY 
        CASE w.difficulty_level 
            WHEN 'beginner' THEN 1 
            WHEN 'intermediate' THEN 2 
            WHEN 'advanced' THEN 3 
        END,
        w.name ASC
");

if ($selected_difficulty) {
    $stmt->execute([$_SESSION['user_id'], $selected_difficulty]);
} else {
    $stmt->execute([$_SESSION['user_id']]);
}
$workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group workouts by difficulty
$grouped_workouts = [];
foreach ($workouts as $workout) {
    $grouped_workouts[$workout['difficulty_level']][] = $workout;
}

// Get count of workouts per difficulty
$stmt = $db->prepare("
    SELECT difficulty_level, COUNT(*) as count
    FROM workouts
    WHERE difficulty_level IN ('beginner', 'intermediate', 'advanced')
    GROUP BY difficulty_level
");
$stmt->execute();
$difficulty_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<?php if ($redirect_url): ?>
<script>
    // Show toast message if there is one
    <?php if ($message): ?>
    M.toast({html: '<?php echo $message; ?>', classes: 'rounded'});
    <?php endif; ?>
    
    // Redirect after a short delay
    setTimeout(function() {
        window.location.href = '<?php echo $redirect_url; ?>';
    }, 1000);
</script>
<?php endif; ?>

<main>
    <div class="workouts-container">
        <?php if (!$subscription): ?>
            <!-- No Subscription -->
            <div class="card">
                <div class="card-content">
                    <div class="card-panel blue lighten-4">
                        <i class="material-icons left">info</i>
                        You need an active subscription to access workouts. 
                        <a href="plans.php" class="btn-small blue right">View Plans</a>
                    </div>
                </div>
            </div>
        <?php elseif ($subscription['status'] === 'pending'): ?>
            <!-- Pending Subscription -->
            <div class="card">
                <div class="card-content">
                    <div class="card-panel orange lighten-4">
                        <i class="material-icons left">pending</i>
                        <strong>Subscription Status: Pending Approval</strong><br>
                        Your subscription is currently pending approval. You'll be able to access all workouts once your subscription is approved.
                        <br><br>
                        <a href="subscription.php" class="btn-small orange">View Subscription Status</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Active Subscription -->
            <div class="row">
                <div class="col s12">
                    <!-- Difficulty Level Cards -->
                    <?php if (!$selected_difficulty): ?>
                        <div class="card">
                            <div class="card-content">
                                <div class="row" style="margin-bottom: 0;">
                                    <div class="col s6">
                                        <span class="card-title">
                                            <i class="material-icons left">fitness_center</i>
                                            Workout Levels
                                        </span>
                                    </div>
                                    <div class="col s6 right-align">
                                        <a href="saved_items.php" class="btn waves-effect waves-light">
                                            <i class="material-icons left">bookmark</i>
                                            Saved Items
                                        </a>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 20px;">
                                    <div class="col s12 m6 l4">
                                        <div class="card hoverable">
                                            <div class="card-content center-align">
                                                <i class="material-icons medium green-text">accessibility_new</i>
                                                <h5>Beginner</h5>
                                                <p class="grey-text">Perfect for those just starting their fitness journey</p>
                                                <div class="chip">
                                                    <?php echo $difficulty_counts['beginner'] ?? 0; ?> Workouts
                                                </div>
                                            </div>
                                            <div class="card-action center-align">
                                                <a href="?level=beginner" class="btn waves-effect waves-light green">
                                                    View Workouts
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col s12 m6 l4">
                                        <div class="card hoverable">
                                            <div class="card-content center-align">
                                                <i class="material-icons medium orange-text">directions_run</i>
                                                <h5>Intermediate</h5>
                                                <p class="grey-text">For those ready to push their limits</p>
                                                <div class="chip">
                                                    <?php echo $difficulty_counts['intermediate'] ?? 0; ?> Workouts
                                                </div>
                                            </div>
                                            <div class="card-action center-align">
                                                <a href="?level=intermediate" class="btn waves-effect waves-light orange">
                                                    View Workouts
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col s12 m6 l4">
                                        <div class="card hoverable">
                                            <div class="card-content center-align">
                                                <i class="material-icons medium red-text">whatshot</i>
                                                <h5>Advanced</h5>
                                                <p class="grey-text">Challenging workouts for experienced fitness enthusiasts</p>
                                                <div class="chip">
                                                    <?php echo $difficulty_counts['advanced'] ?? 0; ?> Workouts
                                                </div>
                                            </div>
                                            <div class="card-action center-align">
                                                <a href="?level=advanced" class="btn waves-effect waves-light red">
                                                    View Workouts
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Workouts List -->
                    <?php if ($selected_difficulty && isset($grouped_workouts[$selected_difficulty])): ?>
                        <div class="card">
                            <div class="card-content">
                                <div class="row" style="margin-bottom: 0;">
                                    <div class="col s6">
                                        <span class="card-title">
                                            <i class="material-icons left">fitness_center</i>
                                            <?php echo ucfirst($selected_difficulty); ?> Workouts
                                        </span>
                                    </div>
                                    <div class="col s6 right-align">
                                        <a href="workouts.php" class="btn-flat waves-effect">
                                            <i class="material-icons left">arrow_back</i>
                                            Back to Levels
                                        </a>
                                    </div>
                                </div>

                                <div class="row workouts-grid">
                                    <?php foreach ($grouped_workouts[$selected_difficulty] as $workout): ?>
                                        <div class="col s12 m6 l4">
                                            <div class="card workout-card hoverable">
                                                <div class="card-content">
                                                    <span class="card-title truncate">
                                                        <?php echo htmlspecialchars($workout['name']); ?>
                                                        <?php if ($workout['is_saved']): ?>
                                                            <i class="material-icons right yellow-text text-darken-2">bookmark</i>
                                                        <?php endif; ?>
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
                                                            <form method="post" style="display: inline;">
                                                                <input type="hidden" name="workout_id" value="<?php echo $workout['id']; ?>">
                                                                <?php if ($workout['is_saved']): ?>
                                                                    <button type="submit" name="remove_workout" class="btn-floating waves-effect waves-light yellow darken-2">
                                                                        <i class="material-icons">bookmark</i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button type="submit" name="save_workout" class="btn-floating waves-effect waves-light grey">
                                                                        <i class="material-icons">bookmark_border</i>
                                                                    </button>
                                                                <?php endif; ?>
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
                                                    <?php if ($workout['is_saved']): ?>
                                                        <i class="material-icons right yellow-text text-darken-2">bookmark</i>
                                                    <?php endif; ?>
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
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="workout_id" value="<?php echo $workout['id']; ?>">
                                                    <?php if ($workout['is_saved']): ?>
                                                        <button type="submit" name="remove_workout" class="btn-flat waves-effect waves-light">
                                                            <i class="material-icons left">bookmark</i>
                                                            Remove from Saved
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="save_workout" class="btn-flat waves-effect waves-light">
                                                            <i class="material-icons left">bookmark_border</i>
                                                            Save Workout
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                                <a href="#!" class="modal-close waves-effect waves-light btn-flat">Close</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.workouts-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.difficulty-filters {
    margin: 20px 0;
}

.difficulty-filters .btn-small {
    margin: 0 5px;
}

.workouts-grid {
    margin-top: 20px;
}

.workout-card {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.workout-card .card-content {
    flex-grow: 1;
}

.workout-card .card-title {
    font-size: 1.4rem;
    margin-bottom: 10px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}

.workout-card .card-title i {
    font-size: 1.4rem;
}

.workout-card p {
    margin: 8px 0;
    display: flex;
    align-items: center;
}

.workout-card i.tiny {
    margin-right: 8px;
}

.workout-card .category {
    color: #666;
}

.modal.modal-fixed-footer {
    max-height: 85%;
    height: 85%;
    width: 90%;
    max-width: 800px;
}

.badges {
    margin: 15px 0;
}

.badges .badge {
    margin-right: 8px;
    padding: 5px 10px;
    border-radius: 4px;
}

.instructions, .equipment-list {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 4px;
    margin: 10px 0;
}

.instructions ol, .equipment-list ul {
    margin: 0;
    padding-left: 20px;
}

.instructions li, .equipment-list li {
    margin-bottom: 8px;
}

.card-panel {
    margin: 0.5rem 0;
    padding: 20px;
    border-radius: 4px;
}

.card-panel i.left {
    margin-right: 10px;
}

.btn-small {
    height: 32px;
    line-height: 32px;
    padding: 0 12px;
}

.btn-small i.left {
    margin-right: 5px;
}

@media only screen and (max-width: 600px) {
    .workouts-container {
        padding: 10px;
    }
    
    .difficulty-filters .btn-small {
        margin: 5px;
        display: inline-block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals, {
        dismissible: true,
        inDuration: 300,
        outDuration: 200
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
