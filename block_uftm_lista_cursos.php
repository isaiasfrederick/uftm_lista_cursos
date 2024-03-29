<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for editing HTML block instances.
 *
 * @package   block_html
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//include "utilitarios.php";
include "./utilitarios.php";

function configuracaoValidaNoBanco2() {
    return false;
}

class block_uftm_lista_cursos extends block_base {

    function init() {
        //$this->title = get_string('pluginname', 'block_html');
        //$this->title = get_string(identifier: 'pluginname', component:'block_testblock');
        $this->title = "Salas que você participa:";
    }

    function has_config() {
        return true;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    /*function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('newhtmlblock', 'block_html');
        }
    }*/

    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
        try {
            global $DB;
            global $USER;
            global $OUTPUT;
            global $CFG;

            if ($this->content !== NULL) {
                return $this->content;
            }

            // Ver mais sobre a Data manipulation API
            $userstring = '';
            $this->content = new stdClass;
            //$this->content->text = $userstring;
            //$this->content->footer = 'Texto de rodapé do plugin (bloco)';
            $this->content->items = array();

            /*require_once($CFG->dirroot."/blocks/uftm_lista_cursos/block_utilitarios.php");
            include($CFG->dirroot."/blocks/uftm_lista_cursos/block_utilitarios.php");

            if (false === configuracaoValidaNoBanco()) {
                throw new Exception('Configuracao invalida no banco');
            }*/

            /*$anchor = html_writer::tag('a', $USER->firstname . ' '. $USER->lastname, array('href' => 'http://www.globo.com'));
            $this->content->text = html_writer::div($anchor, '', array('id'=>$USER->id));*/

            $sqlEntrada =
            "SELECT
                c.id AS cid,
                c.fullname,
                c.shortname,
                cc.name AS catname,
                u.id,
                CASE WHEN UltimoAcesso.tc <> 0  THEN TO_CHAR(TO_TIMESTAMP(UltimoAcesso.tc), 'YYYY/mm/dd') ELSE 'Nunca acessada'  END AS datacriacao,
                CASE WHEN c.startdate <> 0 THEN TO_CHAR(TO_TIMESTAMP(c.startdate), 'YYYY/mm/dd') ELSE 'Nunca acessada' END AS startdate            FROM {user} u
            INNER JOIN {role_assignments} ra ON ra.userid = u.id
            INNER JOIN {context} ct ON ct.id = ra.contextid
            INNER JOIN {course} c ON c.id = ct.instanceid
            INNER JOIN {role} r ON r.id = ra.roleid
            INNER JOIN {course_categories} cc ON c.category = cc.id 
            LEFT JOIN 
            (
                SELECT DISTINCT
                    l.userid AS luid,
                    l.courseid AS cid,
                    MAX(l.timecreated) AS tc
                FROM {logstore_standard_log} l
                WHERE l.eventname = '\\core\\event\\course_viewed'
                GROUP BY luid, cid
                ORDER BY tc DESC
            ) AS UltimoAcesso ON UltimoAcesso.cid = c.id AND UltimoAcesso.luid = u.id
            WHERE u.id = ? AND c.visible = 1";

            $registros = $DB->get_records_sql($sqlEntrada, array($USER->id), 0);

            $dados = [];

            // usando a variável de ambiente $USER para ver os dados de nome e sobrenome
            $dados['nomeAluno'] = $USER->firstname . ' ' . $USER->lastname;
            $todas_disciplinas = array();

            // component, action, target, 
            foreach ($registros as $reg) {
                $urlthumb = \core_course\external\course_summary_exporter::get_course_image(get_course($reg->cid));

                if (!$urlthumb)
                    $urlthumb = 'http://localhost/moodle/pix/semthumb3.jpg';

                array_push($todas_disciplinas, array(
                    'id' => $reg->cid,
                    'fullname' => $reg->fullname,
                    'shortname' => $reg->shortname,
                    'datacriacao' => $reg->datacriacao,
                    'url' => $CFG->wwwroot . '/course/view.php?id=' . $reg->cid,
                    'thumb' => $urlthumb,
                    'startdate' => $reg->startdate,
                    'category' => $reg->catname
                    //, 'thumb' => \core_course\external\course_summary_exporter::get_course_image(get_course($reg->cid))
                ));
            }

            // Exibindo, ainda em JSON, o resultado de uma consulta SQL que contém um INNER JOIN
            $dados['disciplinas'] = $todas_disciplinas;

            // Renderizando o template e passando dados para ele
            $this->content->text = $OUTPUT->render_from_template('block_uftm_lista_cursos/bloco', $dados);
            return $this->content;

        } catch (\Exception $e) {
            $this->content->text = $e->getMessage();
            return $this->content;
        } catch (Exception $exc) {
            $this->content->text = $e->getMessage();
            return $this->content;
        }
    }
    
}
