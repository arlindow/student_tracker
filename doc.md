Student Tracker — Documentação Técnica
Plugin local_student_tracker · release 0.1.0 (alpha) · Moodle 4.1 ou superior · 05/11/2026
1. Visão geral
O Student Tracker é um plugin local do Moodle que monitora o acesso dos alunos dentro de cada curso. Foi pensado para a turma de Análise e Desenvolvimento de Sistemas (UAB, EAD). Ele classifica os alunos matriculados pelo último acesso ao curso, exibe os totais em cartões clicáveis e lista os alunos em uma tabela que pode ser filtrada por status.
Categoria
Regra
Cor do cartão
Nunca acessaram
Sem registro de acesso ao curso
Vermelho
Em risco
Último acesso há mais de N dias
Amarelo
Ativos
Último acesso dentro dos últimos N dias
Verde
O valor de N é a configuração riskdays (padrão: 7 dias). As categorias são mutuamente exclusivas, portanto nunca + risco + ativos = total de alunos.
2. Requisitos e compatibilidade
• Moodle 4.1 LTS ou superior (requires = 2022112800).
• Tema Boost com Bootstrap 4 (classes badge-danger, badge-warning, badge-success). No Moodle 5.0 (Bootstrap 5), troque por badge bg-danger e equivalentes.
• Papel de aluno com o nome curto padrão student.
• Maturidade: alfa. Não recomendado para produção sem testes.
3. Estrutura de arquivos
moodle/
└── local/
    └── student_tracker/
        ├── version.php
        ├── index.php
        ├── lib.php
        ├── settings.php
        ├── db/
        │   └── access.php
        ├── classes/
        │   └── privacy/
        │       └── provider.php
        └── lang/
            ├── en/
            │   └── local_student_tracker.php
            └── pt_br/
                └── local_student_tracker.php
Arquivo
Função
version.php
Identificação, versão e compatibilidade do plugin
db/access.php
Define a permissão de visualizar o painel (contexto de curso)
lib.php
Adiciona o link do painel na navegação do curso
settings.php
Configuração riskdays na administração do site
index.php
Página do painel: consulta, cartões e tabela
classes/privacy/provider.php
Declaração de privacidade (o plugin não armazena dados)
lang/*/local_student_tracker.php
Textos da interface
4. Código-fonte
4.1 version.php
<?php
// Impede o acesso direto ao arquivo pelo navegador.
defined('MOODLE_INTERNAL') || die();

// Nome do plugin no formato tipo_nome; deve coincidir com a pasta local/student_tracker.
$plugin->component = 'local_student_tracker';

// Formato YYYYMMDDXX. O Moodle compara com o valor gravado no banco
// e dispara a atualização quando este número é maior.
// Incremente sempre que alterar db/access.php ou o banco de dados.
$plugin->version   = 2026110500;

// Versão mínima do Moodle: 4.1 LTS.
$plugin->requires  = 2022112800;

// Maturidade: ALPHA, BETA, RC ou STABLE.
$plugin->maturity  = MATURITY_ALPHA;

// Versão legível exibida na lista de plugins do administrador.
$plugin->release   = '0.1.0';
4.2 db/access.php
<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Formato "tipo/nome:ação"; precisa bater com o componente do plugin.
    'local/student_tracker:view' => [
        // 'read' = apenas visualização (não altera dados).
        'captype'      => 'read',
        // A permissão é avaliada dentro do curso. Professores com papel
        // atribuído apenas no curso passam a ter acesso; gerentes herdam
        // o acesso em todos os cursos.
        'contextlevel' => CONTEXT_COURSE,
        // Papéis que recebem a permissão por padrão na instalação.
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
    ],
];
4.3 lib.php
<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Adiciona o link "Student Tracker" na navegação do curso
 * (aba "Mais" / menu do curso no Moodle 4.x).
 * O nome segue o padrão local_<plugin>_extend_navigation_course;
 * se o prefixo não bater com o componente, o Moodle ignora a função.
 */
function local_student_tracker_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
) {
    // Não exibe no curso "Página inicial" do site.
    if ($course->id == SITEID) {
        return;
    }

    // Só mostra o link para quem tem a permissão no contexto deste curso.
    if (!has_capability('local/student_tracker:view', $context)) {
        return;
    }

    // O id do curso segue na URL e é lido por index.php.
    $url = new moodle_url('/local/student_tracker/index.php', ['id' => $course->id]);

    $navigation->add(
        get_string('pluginname', 'local_student_tracker'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'student_tracker',            // chave única do nó
        new pix_icon('i/report', '')  // ícone
    );
}
Esta função roda em toda página do curso. Mantenha-a leve: apenas checagem de permissão, sem consultas ao banco.
4.4 settings.php
<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_student_tracker',
        get_string('pluginname', 'local_student_tracker')
    );
    $ADMIN->add('localplugins', $settings);

    // Dias sem acesso para classificar o aluno como "em risco".
    $settings->add(new admin_setting_configtext(
        'local_student_tracker/riskdays',
        get_string('riskdays', 'local_student_tracker'),
        get_string('riskdays_desc', 'local_student_tracker'),
        7,
        PARAM_INT
    ));
}
4.5 lang/pt_br/local_student_tracker.php
As chaves ficam em ordem alfabética, padrão do Moodle. Crie também lang/en/ com as mesmas chaves, pois o inglês é o idioma de reserva.
<?php
defined('MOODLE_INTERNAL') || die();

$string['active']          = 'Estudantes ativos';
$string['at_risk']         = 'Em risco (> {$a} dias sem acesso)';
$string['clear_filter']    = 'Limpar filtro';
$string['col_email']       = 'E-mail';
$string['col_lastaccess']  = 'Último acesso ao curso';
$string['col_name']        = 'Nome';
$string['col_status']      = 'Status';
$string['dashboard_title'] = 'Acompanhamento de alunos';
$string['never']           = 'Nunca';
$string['never_accessed']  = 'Nunca acessaram';
$string['no_students']     = 'Nenhum estudante matriculado encontrado.';
$string['pluginname']      = 'Student Tracker';
$string['privacy:metadata'] = 'O plugin Student Tracker não armazena dados pessoais.';
$string['riskdays']        = 'Dias para considerar "em risco"';
$string['riskdays_desc']   = 'Alunos sem acesso ao curso há mais dias que este valor são classificados como em risco.';
$string['status_active']   = 'Ativo';
$string['status_never']    = 'Nunca acessou';
$string['status_risk']     = 'Em risco';
$string['student_tracker:view'] = 'Visualizar o painel de acompanhamento de alunos';
$string['total_students']  = 'Total de estudantes: {$a}';
4.6 classes/privacy/provider.php
<?php
namespace local_student_tracker\privacy;

defined('MOODLE_INTERNAL') || die();

// O plugin apenas lê dados do núcleo e não armazena nada próprio.
class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
4.7 index.php
<?php
require_once(__DIR__ . '/../../config.php');

// ---------- 1. Parâmetros e permissões ----------
$courseid = required_param('id', PARAM_INT);                // enviado pelo lib.php
$filter   = optional_param('status', '', PARAM_ALPHA);      // never | risk | active

$course  = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);                                      // logado e com acesso ao curso
require_capability('local/student_tracker:view', $context);  // permissão do plugin

// ---------- 2. Página ----------
$PAGE->set_url('/local/student_tracker/index.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginname', 'local_student_tracker'));
$PAGE->set_heading($course->fullname);

// ---------- 3. Regra de corte ----------
$riskdays = (int) (get_config('local_student_tracker', 'riskdays') ?: 7);
$limit    = time() - ($riskdays * DAYSECS);

// ---------- 4. Consulta: um registro por aluno ----------
// Campos exigidos por fullname() para evitar avisos de debug.
$namefields = 'u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, '
            . 'u.middlename, u.alternatename';

// - Só matrículas e métodos de inscrição ativos.
// - O papel de aluno é amarrado ao contexto DESTE curso.
// - user_lastaccess guarda o último acesso ao curso (LEFT JOIN: quem nunca
//   entrou não tem linha). MAX + COALESCE geram 0 para "nunca".
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

// ---------- 5. Categorização exclusiva ----------
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

    $counts[$status]++;                       // cartões mostram sempre o total geral
    $student->tracker_status = $status;

    if ($filter === '' || $filter === $status) {
        $rows[] = $student;                   // tabela respeita o filtro
    }
}
$total = count($students);

// ---------- 6. Metadados de exibição ----------
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

// ---------- 7. Renderização ----------
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
5. Decisões de projeto
Decisão
Motivo
Permissão em CONTEXT_COURSE em vez de CONTEXT_SYSTEM
Professores têm papel atribuído no curso, não no site; no contexto de sistema eles seriam barrados.
Navegação por extend_navigation_course
A navegação global do Moodle 4.x nem sempre exibe itens de plugins, e o painel é por curso.
Último acesso vindo só de user_lastaccess
O campo lastaccess da tabela user é o acesso ao site e esconderia quem nunca abriu o curso.
Categorias mutuamente exclusivas
A soma dos cartões fecha com o total da turma.
Regra de corte configurável (riskdays)
Evita número fixo no código e nos textos.
6. Segurança e privacidade (LGPD)
• Parâmetros limpos com PARAM_* e consulta SQL com parâmetros nomeados (sem concatenar valores do usuário).
• require_login seguido de require_capability antes de qualquer dado ser lido.
• Toda saída de texto do usuário é escapada com s().
• Os dados exibidos (nome, e-mail, último acesso) são pessoais. Restrinja a permissão apenas a quem precisa e declare o plugin como null_provider, já que ele não armazena dados próprios.
7. Instalação e validação
1. Copie a pasta student_tracker para moodle/local/.
2. Acesse Administração do site > Notificações e conclua a instalação.
3. Em Administração > Plugins > Plugins locais > Student Tracker, confirme o valor de riskdays.
4. Limpe os caches em Administração > Desenvolvimento > Limpar todos os caches.
5. Abra o curso da turma e procure o link Student Tracker na aba Mais.
6. Teste com três perfis: gerente, professor do curso e aluno (o aluno não deve ver o link nem acessar a URL).
7. Confira se nunca + risco + ativos = total e se os filtros dos cartões funcionam.
Se o link não aparecer, verifique a permissão em Administração do curso > Participantes > Permissões e confirme que o version foi incrementado.
8. Pendências e próximos passos
• Migrar o HTML do index.php para um template Mustache e a consulta para uma classe em classes/.
• Paginação da tabela para turmas grandes (flexible_table ou limitfrom/limitnum).
• Filtro por polo.
• Botão para enviar mensagem aos alunos de uma categoria (API de mensagens do Moodle).
• Escala de status mais detalhada (atenção, risco, possível evasão).
• Monitoramento de realização de atividades.
• Testes automatizados (PHPUnit e Behat).
• Versão em inglês dos textos em lang/en/.