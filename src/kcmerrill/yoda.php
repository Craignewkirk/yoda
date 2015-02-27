<?php
namespace kcmerrill;

class yoda {
    var $app;
    var $action;
    var $modifier;
    var $version = 0.05;
    var $args;
    var $spoke = false;
    var $lifted = array();
    var $summoning = false;
    function __construct($app, $action = false, $modifier = false, $args = array()) {
        $this->app = $app;
        $this->action = $action;
        $this->modifier = $modifier;
        $this->args = is_array($args) ? $args : array();
        $this->speak();
        if(filemtime($this->app['config']->c('yoda.root_dir') . '/yoda.last_updated') + 604800 <= time()) {
            $this->update();
        }
        try {
            $this->$action($modifier);
         } catch (\Exception $e) {
            $this->app['cli']->out('<green>[Yoda]</green> <red>' . $e->getMessage() . '</red>');
         }

    }
    function update($env = false) {
        $cwd = getcwd();
        $root_dir = $this->app['config']->c('yoda.root_dir');
        chdir($root_dir);
        $this->app['cli']->out('<background_green><black>To update I need. herh.</black></background_green>');
        $this->app['shell']->execute('git pull', in_array('--loudly', $this->args));
        chdir($cwd);
        touch($root_dir . '/yoda.last_updated');
    }

    function share($share_as = false) {
        $root_dir = $this->app['config']->c('yoda.root_dir');
        $new_share = $root_dir . '/www/share/' . $share_as;
        if($share_as) {
            if(in_array('--force', $this->args) || !is_file($new_share)) {
                try {
                    mkdir(dirname($new_share), 0755, true);
                }
                catch(\Exception $e) {}
                if(is_file('.yoda')) {
                    file_put_contents($new_share, file_get_contents('.yoda'));
                    $this->app['cli']->out('<green>[Do]</green> <white>' . $share_as . '</white>');
                    $this->app['cli']->out('<green>Shared your wisdom with the world, I have.  Hmmmmmm.</green>');
                } else {
                    throw new \Exception('Have, a valid .yoda file I must.');
                }
            } else {
                throw new \Exception($share_as . ' exists! Use the force(--force) and try again, you should.  Yes, hmmm.');
            }
        } else {
            throw new \Exception('Only share things that are name followed by project, I can!  Yeesssssss. ' . PHP_EOL . 'Eg: yoda share db/mysql');
        }
    }

    function lift($env = false) {
        $original_location = getcwd();
        $this->app['yaml']->smartConfig();
        $config = $this->app['yaml']->configFileContents($env);
        $setup = is_file('.yoda.setup');
        if(in_array('--force', $this->args) && $setup) {
            unlink('.yoda.setup');
        }
        foreach($config as $container_name=>$container_config) {
            $require = is_array($container_config['require']) ? $container_config['require'] : array($container_config['require']);
            $required_project_folder = false;
            foreach($require as $req) {
                chdir('../');
                try {
                    $this->summon($req);
                } catch(\Exception $e) {
                    $this->lift($env);
                }
                chdir($original_location);
            }
            if(in_array($container_config['name'], $this->lifted)) {
                unset($config[$container_name]);
                $this->app['cli']->out('<green>[Yoda]</green><white> ' . $container_config['name'] . ' already running ... </white>');
            } else {
                $this->lifted[] = $container_config['name'];
            }
        }
        $instructions = $this->app['instruct']->lift($config);
        $this->app['shell']->executeLiftInstructions($instructions, $config, in_array('--loudly', $this->args));
        touch('.yoda.setup');
    }

    function seek() {
        $configs = $this->app['yaml']->seekConfigFiles(getcwd());
        foreach($configs as $config) {
            $this->app['cli']->out('<green>[Yoda]</green> <white>Found ... ' . $config . '</white>');
            chdir(dirname($config));
            $this->lift($this->modifier);
        }
    }
    function control() {
        $this->app['yaml']->smartConfig();
        $config = $this->app['yaml']->configFileContents($this->modifier);
        $instructions = $this->app['instruct']->control($config, $this->modifier);
        $this->app['shell']->executeInstructions($instructions, true);
    }
    function summon($project_name) {
        $folder = $project_name;
        if(strpos($folder, '/') === FALSE) {
            throw new \Exception('Only summon things that are name followed by project, I can!  Yeesssssss. ' . PHP_EOL . 'Eg: yoda summon db/mysql');
        } else {
            list($user, $folder) = explode('/', $folder, 2);
            $this->summoning = $folder;
        }
        if(is_dir($folder) && !in_array('--force', $this->args)) {
            chdir(getcwd() . '/' . $folder);
            $this->lift($project_name);
        } else {
            if(!is_file($folder)) {
                @mkdir($folder, 0755, true);
            }
            $repos = $this->app['config']->get('yoda.repos', array('yoda.kcmerrill.com'));
            chdir(getcwd() . '/' . $folder);
            $this->app['yaml']->saveConfigFile($project_name, $repos);
            $this->lift($project_name);
        }
        return $folder;
    }
    function version($modifier = false) {
        $this->app['cli']->out('v' . $this->version);
    }

    function kill($modifier = false) {
        $this->app['shell']->execute($this->app['docker']->killall(), in_array('--loudly', $this->args));
    }
    function speak() {
        if($this->spoke) {
            return true;
        }
$this->app['cli']->out("
           <green>.--.</green>
   <green>\`--._,'.::.`._.--'/</green>       <green>[Do]</green> <white>||</white> <red>[Do Not]</red>
     <green>.  ` __::__ '  .</green>          <white>There is </white>!<yellow>[Try]</yellow>
       <green>- .`'..`'. -</green>
         <green>\ `--' /</green>                      <white>-</white><green>Yoda</green>\n");

        $this->spoke = true;
    }
    function __call($method, $params) {
        throw new \Exception($method . '? I know not what you mean.');
    }
}
