<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Adiciona o link "Student Tracker" na navegação do curso
 * (aba "Mais" / menu do curso no Moodle 4.x).
 */
function local_student_tracker_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
) {
    // Não mostra o link no curso "Página inicial" do site.
    if ($course->id == SITEID) {
        return;
    }

    if (!has_capability('local/student_tracker:view', $context)) {
        return;
    }

    $url = new moodle_url('/local/student_tracker/index.php', ['id' => $course->id]);

    $navigation->add(
        get_string('pluginname', 'local_student_tracker'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'student_tracker',
        new pix_icon('i/report', '')
    );
}
