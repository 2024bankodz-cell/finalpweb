<?php
require_once '../includes/auth.php';
require_login('etudiant');
$pdo = get_pdo();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM etudiants WHERE id = ?');
$stmt->execute([$user_id]);
$u = $stmt->fetch();

$days_fr = ['Sunday'=>'Dimanche','Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi','Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi'];
$today_fr = $days_fr[date('l')];

function buildSharedSchedule(array $modules) {
    $days = ['Samedi', 'Dimanche', 'Lundi', 'Mardi', 'Mercredi'];
    $times = ['08:00:00', '10:00:00', '12:00:00', '14:00:00', '16:00:00', '18:00:00'];
    $rooms = ['Salle 101', 'Salle 102', 'Salle 201', 'Salle 202', 'Amphi A', 'Amphi B'];
    $schedule = [];
    foreach ($modules as $index => $module) {
        $day = $days[$index % count($days)];
        $schedule[$day][] = [
            'jour' => $day,
            'module_name' => $module['code'] . ' — ' . $module['intitule'],
            'heure_debut' => $times[$index % count($times)],
            'salle' => $rooms[$index % count($rooms)],
        ];
    }
    return $schedule;
}

$stmt = $pdo->prepare('SELECT m.code, m.intitule FROM inscriptions i JOIN modules m ON m.id = i.module_id WHERE i.etudiant_id = ? AND i.annee_univ = ? ORDER BY m.code ASC');
$stmt->execute([$user_id, '2025/2026']);
$module_list = $stmt->fetchAll();
$shared_schedule = buildSharedSchedule($module_list);
$today_schedule = $shared_schedule[$today_fr] ?? [];

$stmt = $pdo->prepare('SELECT m.code, n.note_tp, n.note_td, n.note_exam FROM inscriptions i JOIN modules m ON m.id = i.module_id LEFT JOIN notes n ON n.etudiant_id = i.etudiant_id AND n.module_id = i.module_id WHERE i.etudiant_id = ?');
$stmt->execute([$user_id]);
$all_notes = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT m.code, COALESCE(a.nombre, 0) AS nombre
    FROM inscriptions i
    JOIN modules m ON m.id = i.module_id
    LEFT JOIN absences a ON a.etudiant_id = i.etudiant_id AND a.module_id = i.module_id AND a.annee_univ = i.annee_univ
    WHERE i.etudiant_id = ? AND i.annee_univ = ?
    ORDER BY m.code ASC');
$stmt->execute([$user_id, '2025/2026']);
$all_absences = $stmt->fetchAll();

function calculateFinal($tp, $td, $exam) {
    if ($exam === null) return null;
    if ($tp === null && $td === null) {
        return round($exam * 0.6, 2);
    }
    if ($tp === null) {
        return round(($td * 0.4) + ($exam * 0.6), 2);
    }
    if ($td === null) {
        return round(($tp * 0.2) + ($exam * 0.8), 2);
    }
    return round(($tp * 0.2) + ($td * 0.2) + ($exam * 0.6), 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USTHB Dashboard - <?= htmlspecialchars($u['prenom']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo"><img src="../img/usthb.png" class="logo-img" alt="USTHB Logo"><span>USTHB</span></div>
            <nav>
                <a href="student.php" class="nav-item active">Dashboard</a>
                <a href="classes.php" class="nav-item">My Classes</a>
                <a href="assignments.php" class="nav-item">Assignments</a>
                <a href="grades.php" class="nav-item">Grades</a>
                <a href="../public/logout.php" class="nav-logout">Logout</a>
            </nav>
        </aside>
        <main>
            <header><div class="spacer"></div><div class="user"><span><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></span><div class="avatar"></div></div></header>
            <div class="content">
                <h1 style="font-size: 28px; margin-bottom: 8px;">Welcome back, <?= htmlspecialchars($u['prenom']) ?> 👋</h1>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 24px;">It's <?= $today_fr ?>, <?= date('F jS') ?></p>
                <div class="card">
                    <span class="card-label">GRADING POLICY</span>
                    <div class="grading-flex">
                        <div class="policy-main">
                            <div class="policy-item"><span class="big">40%</span><br><span class="small">TD (Continuous)</span></div>
                            <div class="policy-item"><span class="big">60%</span><br><span class="small">Final Exam</span></div>
                        </div>
                        <div class="tp-box">
                            <h4>Modules with TP</h4>
                            <div class="tp-grid">
                                <div class="tp-col"><span>20%</span><small>TP</small></div>
                                <div class="tp-col"><span>20%</span><small>TD</small></div>
                                <div class="tp-col"><span>60%</span><small>Exam</small></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="main-row">
                    <div class="left-col">
                        <div class="card">
                            <h3 style="margin-bottom:20px; color:#1e4f8c; font-size:16px;">Today's Schedule</h3>
                            <?php if (empty($today_schedule)): ?>
                                <div style="padding:20px; border-radius:16px; background:#ecfdf5; color:#166534; font-weight:600; border:1px solid #d1fae5;">
                                    You don't have classes today — enjoy your day!
                                </div>
                            <?php else: ?>
                                <?php foreach($today_schedule as $item): ?>
                                <div class="schedule-item" style="flex-direction:row;"><div class="schedule-date"><span><?= strtoupper(date('M')) ?></span><strong><?= date('d') ?></strong></div><div style="flex:1"><h4><?= htmlspecialchars($item['module_name']) ?></h4><p><?= htmlspecialchars($item['heure_debut']) ?> • <?= htmlspecialchars($item['salle']) ?></p></div></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="card">
                            <h3 style="margin-bottom:20px; color:#1e4f8c; font-size:16px;">Performance</h3>
                            <?php foreach($all_notes as $n): 
                                $fNote = calculateFinal($n['note_tp'], $n['note_td'], $n['note_exam']);
                            ?>
                            <div class="grade-row">
                                <div class="grade-info"><span><?= htmlspecialchars($n['code']) ?></span><span><?= $fNote !== null ? number_format($fNote, 2) . '/20' : 'N/A' ?></span></div>
                                <div class="grade-track"><div class="grade-fill" style="width:<?= $fNote !== null ? (($fNote/20)*100) : 0 ?>%;"></div></div>
                                <div style="display:flex; justify-content:space-between; font-size:12px; color:#475569; margin-top:10px; gap:12px;">
                                    <span>TP: <?= $n['note_tp'] !== null ? htmlspecialchars($n['note_tp']) . '/20' : '-' ?></span>
                                    <span>TD: <?= $n['note_td'] !== null ? htmlspecialchars($n['note_td']) . '/20' : '-' ?></span>
                                    <span>Exam: <?= $n['note_exam'] !== null ? htmlspecialchars($n['note_exam']) . '/20' : '-' ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="right-col">
                        <div class="card">
                            <h3 style="margin-bottom:20px; color:#1e4f8c; font-size:16px;">Absence Guard</h3>
                            <?php foreach($all_absences as $abs): ?>
                            <div class="absence-item">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div style="flex:1"><h4><?= htmlspecialchars($abs['code']) ?></h4><p style="font-size:11px; color:#64748b;">Remaining Limit</p></div>
                                    <div style="text-align:right;">
                                        <span style="font-weight:700; color: <?= ($abs['nombre'] >= 5) ? '#dc2626' : 'inherit' ?>;"><?= $abs['nombre'] ?>/5</span>
                                        <div class="dots"><span class="dot <?= ($abs['nombre'] >= 1) ? 'filled' : '' ?>"></span><span class="dot <?= ($abs['nombre'] >= 2) ? 'filled' : '' ?>"></span><span class="dot <?= ($abs['nombre'] >= 3) ? 'filled' : '' ?>"></span><span class="dot <?= ($abs['nombre'] >= 4) ? 'filled' : '' ?>"></span><span class="dot <?= ($abs['nombre'] >= 5) ? 'filled' : '' ?>"></span></div>
                                    </div>
                                </div>
                                <?php if($abs['nombre'] >= 5): ?><div class="excluded-msg">⚠️ You are excluded from this module</div><?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
