<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Db_migrate extends Controller {
    function Db_migrate() {
        parent::Controller();
        $this->load->dbforge();
        if (!$this->db->table_exists('tb_schema_migrate')) {
            $fields = array(
                'id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '128',
                )
            );
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('tb_schema_migrate');
        }
    }

    function index() {
        $this->load->helper('directory');
        $this->db->select('id')->from('tb_schema_migrate');
        $query = $this->db->get();
        $results = $query->result_array();
        $array = array();
        foreach ($results as $result) {
            $array[] = $result['id'];
        }

        $exists = directory_map('./system/application/models/migrations/','TRUE');
        $results = array_diff($exists,$array);

        foreach ($results as $result) {
            $class = substr($result,0,-4);
            $this->load->model('migrations/'.$class);
            echo 'init '.$class.anchor('db_migrate/insert/'.$class, '#');
            $this->$class->up();
            $this->db->insert('tb_schema_migrate',array('id'=>$result));
            echo $class.' migrated.';
            log_message('info', $class.' migrated.');
        }
    }

    /**
     *
     * if provide key, un-roll one migration, else, list possible keys
     * @param <string> $key migration key
     */
    function down($key='') {
        if (empty($key)) {
            $this->db->select('id')->from('tb_schema_migrate');
            $query = $this->db->get();
            $results = $query->result_array();
            foreach ($results as $result) {
                echo '<p>'.anchor('db_migrate/down/'.substr($result['id'],0,-4),$result['id']).'</p>';
            }
        } else {
            $this->load->model('migrations/'.$key);
            $this->$class->down();
            $this->db->delete('tb_schema_migrate',array('id'=>$key.'.php'));
            echo $class.' migrated(down).';
            log_message('info', $class.' migrated(down).');
        }
    }

    /**
     * return MD5 sum of schema
     */
    function check() {
        $tables = $this->db->list_tables();
        $fields = array();
        $md5 = array();
        foreach ($tables as $table) {
            $fields[$table] = $this->db->field_data($table);
            $md5[$table] = md5(serialize($fields[$table]));
        }
        echo md5(serialize($fields));
        krumo($fields);
        krumo($md5);
    }

    /**
     * Add migration code to database to skip some migrations
     * @param <string> $migrate_code to add
     * @return <type>
     */
    function insert($migrate_code='') {
        if (empty ($migrate_code)) {
            echo 'No migration code passed';
            return;
        }
        $this->db->insert('tb_schema_migrate',array('id'=>$migrate_code.'.php'));
        log_message('info', $class.' manual inserted.');
        anchor('db_migrate', 'Continue migration');
    }

    /**
     *
     * @param <string> $model name, example address_model
     */
    function fixtures($class='') {
        $this->load->helper('directory');
        if (empty($class)) {
            //list all models
            $models = directory_map('./system/application/models/','TRUE');
            //krumo($models);
            foreach ($models as $key => $model) {
                if (substr($model,-3)!='php') {
                    continue;
                }
                $class = substr($model,0,-4);
                echo '<div>'.anchor('db_migrate/fixtures/'.$class, $class).'</div>';
            }
            echo '<div>'.anchor('db_migrate/fixtures/all', 'ALL MODELS').'</div>';
        } elseif($class=='all') {
            $models = directory_map('./system/application/models/','TRUE');
            foreach ($models as $model) {
                if (substr($model,-3)!='php') {
                    continue;
                }
                $class = substr($model,0,-4);
                $this->load->model($class);
                if (method_exists($this->$class,'fixtures')) {
                    $this->$class->fixtures();
                    echo '<div>'.$class.' Fixture loaded.'.'</div>';
                }
            }
        } else {
            $this->load->model($class);
            $this->$class->fixtures();
            echo '<div>'.$class.' Fixture loaded.'.'</div>';
        }
    }
}
?>
