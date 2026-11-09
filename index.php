<?php
require_once(__DIR__ . '/../../config.php');

// 1. Parâmetros e contexto (curso)
$courseid = required_param('id', PARAM_INT);
$filter   = optional_param('status', '', PARAM_ALPHA); // active | attention | risk | dropout | never

$course  = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/student_tracker:view', $context);

// 2. Configuração da página
$PAGE->set_url('/local/student_tracker/index.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginname', 'local_student_tracker'));
$PAGE->set_heading($course->fullname);

// 3. Réguas de corte temporais (em segundos)
$now = time();
$limit_7d  = $now - (7 * DAYSECS);
$limit_14d = $now - (14 * DAYSECS);
$limit_30d = $now - (30 * DAYSECS);

// 4. Consulta SQL: Busca estudantes com matrícula ativa e último acesso ao curso
$namefields = 'u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, '
            . 'u.middlename, u.alternatename';

$sql = "SELECT u.id, $namefields, u.email,
               COALESCE(MAX(ul.timeaccess), 0) AS lastcourseaccess
          FROM {user} u
          JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.status = :uestatus
          JOIN {enrol} e ON e.id = ue.enrolid
                        AND e.courseid = :courseid AND e.status = :estatus
          JOIN {context} ctx ON ctx.instanceid = e.courseid
                            AND ctx.contextlevel = :ctxlevel
          JOIN {role_assignments} ra ON ra.userid = u.id AND ra.contextid = ctx.id
          JOIN {role} r ON r.id = ra.roleid AND r.shortname = :roleshortname
     LEFT JOIN {user_lastaccess} ul ON ul.userid = u.id AND ul.courseid = e.courseid
         WHERE u.deleted = 0 AND u.suspended = 0
      GROUP BY u.id, $namefields, u.email
      ORDER BY u.lastname, u.firstname";

$params = [
    'uestatus'      => ENROL_USER_ACTIVE,
    'estatus'       => ENROL_INSTANCE_ENABLED,
    'courseid'      => $courseid,
    'ctxlevel'      => CONTEXT_COURSE,
    'roleshortname' => 'student',
];

$students = $DB->get_records_sql($sql, $params);

// 5. Categorização nas 5 faixas pedagógicas
$counts = [
    'active'    => 0,
    'attention' => 0,
    'risk'      => 0,
    'dropout'   => 0,
    'never'     => 0,
];
$rows = [];

foreach ($students as $student) {
    $last = (int) $student->lastcourseaccess;

    if ($last === 0) {
        $status = 'never';
    } elseif ($last >= $limit_7d) {
        $status = 'active';
    } elseif ($last >= $limit_14d) {
        $status = 'attention';
    } elseif ($last >= $limit_30d) {
        $status = 'risk';
    } else {
        $status = 'dropout';
    }

    $counts[$status]++;
    $student->tracker_status = $status;

    if ($filter === '' || $filter === $status) {
        $rows[] = $student;
    }
}
$total = count($students);

// 6. Configuração dos 5 Cards e Badges alinhados com o lang
$cards = [
    'active'    => ['bg-success text-white', 'status_active'],
    'attention' => ['bg-info text-white',    'status_attention'],
    'risk'      => ['bg-warning text-dark',  'status_risk'],
    'dropout'   => ['bg-danger text-white',   'status_dropout'],
    'never'     => ['bg-dark text-white',     'status_never'],
];

$badges = [
    'active'    => ['badge-success', 'status_active'],
    'attention' => ['badge-info',    'status_attention'],
    'risk'      => ['badge-warning', 'status_risk'],
    'dropout'   => ['badge-danger',  'status_dropout'],
    'never'     => ['badge-dark',    'status_never'],
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('dashboard_title', 'local_student_tracker'));
?>

<!-- Grade de Cards com as 5 métricas de acompanhamento -->
<div class="row mb-4">
    <?php foreach ($cards as $key => [$classes, $strkey]): ?>
        <div class="col-md mb-2">
            <a href="<?php echo new moodle_url($PAGE->url, ['status' => $key]); ?>"
               class="card <?php echo $classes; ?> text-decoration-none shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="card-title mb-1"><?php echo get_string($strkey, 'local_student_tracker'); ?></h6>
                    <h3 class="font-weight-bold mb-0">
                        <?php echo $counts[$key]; ?>
                        <small class="h6 font-weight-normal">
                            (<?php echo $total ? round($counts[$key] * 100 / $total) : 0; ?>%)
                        </small>
                    </h3>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <strong><?php echo get_string('total_students', 'local_student_tracker'); ?>:</strong> <?php echo $total; ?>
        <?php if ($filter !== ''): ?>
            <span class="badge badge-secondary ml-2">Filtro: <?php echo get_string('status_' . $filter, 'local_student_tracker'); ?></span>
        <?php endif; ?>
    </div>
    <?php if ($filter !== ''): ?>
        <a href="<?php echo $PAGE->url; ?>" class="btn btn-sm btn-outline-secondary">Limpar filtro</a>
    <?php endif; ?>
</div>

<?php if (empty($rows)): ?>
    <div class="alert alert-info">Nenhum estudante encontrado para esta situação.</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th><?php echo get_string('col_name', 'local_student_tracker'); ?></th>
                    <th><?php echo get_string('col_email', 'local_student_tracker'); ?></th>
                    <th><?php echo get_string('col_lastaccess', 'local_student_tracker'); ?></th>
                    <th><?php echo get_string('col_status', 'local_student_tracker'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $s): ?>
                    <tr>
                        <td><?php echo s(fullname($s)); ?></td>
                        <td><?php echo s($s->email); ?></td>
                        <td>
                            <?php echo $s->lastcourseaccess > 0
                                ? userdate($s->lastcourseaccess, get_string('strftimedatetimeshort', 'core_langconfig'))
                                : get_string('never', 'local_student_tracker'); ?>
                        </td>
                        <td>
                            <?php [$badgeclass, $badgestr] = $badges[$s->tracker_status]; ?>
                            <span class="badge <?php echo $badgeclass; ?> p-2">
                                <?php echo get_string($badgestr, 'local_student_tracker'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
echo $OUTPUT->footer();