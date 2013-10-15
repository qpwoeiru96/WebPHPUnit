<?php
namespace WebPHPUnit\Controller;

class Home extends \WebPHPUnit\Core\Controller {

    protected function _create_snapshot($view_data) {
        
        $directory = \WebPHPUnit\WebPHPUnit::getConfig('snapshot_directory');
        $filename  = realpath($directory). '/' . date('Y-m-d_H-i') . '.html';

        $contents = $this->render_html('partial/test_results', $view_data);

        $handle = fopen($filename, 'a');
        if ( !file_exists($directory) || !$handle ) {
            return array(
                'type'    => 'failed',
                'title'   => '无法创建快照',
                'message' => 'Please ensure that the '
                    . '<code>snapshot_directory</code> in '
                    . '<code>index.php</code> exists and '
                    . 'has the proper permissions.'
            );
        }

        fwrite($handle, $contents);
        fclose($handle);
        return array(
            'type'    => 'succeeded',
            'title'   => '快照创建成功',
            'message' => "快照创建于 <code>{$filename}</code> ."
        );
    }

    // GET
    public function help($request) {
        return array();
    }

    // GET/POST
    public function index($request) {

        if ( $request->is('get') ) {

            $normalize_path = function($path) {
                return str_replace('\\', '/', realpath($path));
            };

            $test_directories = json_encode(array_map(
                $normalize_path, \WebPHPUnit\WebPHPUnit::getConfig('test_directories')
            ));

            $suites                  = array();
            $stats                   = array();
            $create_snapshots        = \WebPHPUnit\WebPHPUnit::getConfig('create_snapshots');
            $sandbox_errors          = \WebPHPUnit\WebPHPUnit::getConfig('sandbox_errors');
            $xml_configuration_files = \WebPHPUnit\WebPHPUnit::getConfig(
                'xml_configuration_files'
            );

            return compact(
                'create_snapshots',
                'sandbox_errors',
                'stats',
                'store_statistics',
                'suites',
                'test_directories',
                'xml_configuration_files'
            );
        }

        $tests = explode('|', $request->data['test_files']);
        $vpu = new \WebPHPUnit\Lib\VPU();

        if ( $request->data['sandbox_errors'] ) {
            error_reporting(\WebPHPUnit\WebPHPUnit::getConfig('error_reporting'));
            set_error_handler(array($vpu, 'handle_errors'));
        }

        $xml_config = false;

        $notifications = array();
        if ( $xml_file_index = $request->data['xml_configuration_file'] ) {
            $files = \WebPHPUnit\WebPHPUnit::getConfig('xml_configuration_files');
            $xml_config = $files[$xml_file_index - 1];
            if ( !$xml_config || !$xml_config = realpath($xml_config) ) {
                $notifications[] = array(
                    'type'    => 'failed',
                    'title'   => 'No Valid XML Configuration File Found',
                    'message' => 'Please ensure that the '
                    . '<code>xml_configuration_file</code> in '
                    . '<code>app/config/bootstrap.php</code> exists and '
                    . 'has the proper permissions.'
                );
            }
        }

        $results = ( $xml_config )
            ? $vpu->run_with_xml($xml_config)
            : $vpu->run_tests($tests);
        $results = $vpu->compile_suites($results, 'web');

        if ( $request->data['sandbox_errors'] ) {
            restore_error_handler();
        }

        $suites  = $results['suites'];
        $stats   = $results['stats'];
        $errors  = $vpu->get_errors();
        $to_view = compact('suites', 'stats', 'errors');

        if ( $request->data['create_snapshots'] ) {
            $notifications[] = $this->_create_snapshot($to_view);
        }

        return $to_view + compact('notifications');
    }
}

?>
