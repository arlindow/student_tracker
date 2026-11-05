<?php
require_once(__DIR__ . '/../../config.php');

// 1. Parâmetros e contexto (curso)
$courseid = required_param('id', PARAM_INT);
$filter   = optional_param('status', '', PARAM_ALPHA); // never | risk | active

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

// 3. Regra de corte configurável (padrão: 7 dias)
$riskdays = (int) (get_config('local_student_tracker', 'riskdays') ?: 7);
$limit    = time() - ($riskdays * DAYSECS);

// 4. Consulta: um registro por aluno, último acesso AO CURSO
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

// 5. Categorias mutuamente exclusivas (nunca + risco + ativos = total)
$counts = ['never' => 0, 'risk' => 0, 'active' => 0];
$rows   = [];

foreach ($students as $student) {
    $last = (int) $student->lastcourseaccess;

    if ($last === 0) {
        $status = 'never';
    } else if ($last < $limit) {
        $status = 'risk';
    } else {
        $status = 'active';
    }

    $counts[$status]++;
    $student->tracker_status = $status;

    if ($filter === '' || $filter === $status) {
        $rows[] = $student;
    }
}
$total = count($students);

// 6. Metadados de exibição
$cards = [
    'never'  => ['bg-danger text-white',  get_string('never_accessed', 'local_student_tracker')],
    'risk'   => ['bg-warning text-dark',  get_string('at_risk', 'local_student_tracker', $riskdays)],
    'active' => ['bg-success text-white', get_string('active', 'local_student_tracker')],
];
$badges = [
    'never'  => ['badge-danger',  'status_never'],
    'risk'   => ['badge-warning', 'status_risk'],
    'active' => ['badge-success', 'status_active'],
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('dashboard_title', 'local_student_tracker'));
?>
<div class="row mb-4">
    <?php foreach ($cards as $key => [$classes, $label]): ?>
        <div class="col-md-4">
            <a href="<?php echo new moodle_url($PAGE->url, ['status' => $key]); ?>"
               class="card <?php echo $classes; ?> mb-3 text-decoration-none">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $label; ?></h5>
                    <h2 class="display-5 font-weight-bold">
                        <?php echo $counts[$key]; ?>
                        <small>(<?php echo $total ? round($counts[$key] * 100 / $total) : 0; ?>%)</small>
                    </h2>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<p>
    <?php echo get_string('total_students', 'local_student_tracker', $total); ?>
    <?php if ($filter !== ''): ?>
        &middot; <a href="<?php echo $PAGE->url; ?>"><?php echo get_string('clear_filter', 'local_student_tracker'); ?></a>
    <?php endif; ?>
</p>

<?php if (empty($rows)): ?>
    <?php echo $OUTPUT->notification(get_string('no_students', 'local_student_tracker'), 'info'); ?>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <table class="table table-striped table-hover">
            <thead>
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
                            <?php [$class, $strkey] = $badges[$s->tracker_status]; ?>
                            <span class="badge <?php echo $class; ?> text-uppercase">
                                <?php echo get_string($strkey, 'local_student_tracker'); ?>
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

