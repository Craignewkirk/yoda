<?php
namespace kcmerrill\yoda;

class instruct {
    var $docker;
    var $instructions;
    function __construct($docker) {
        $this->docker = $docker;
        $this->instructions = array(
            'prompt'=>array(),
            'setup'=>array(),
            'pull'=>array(),
            'build'=>array(),
            'kill'=>array(),
            'remove'=>array(),
            'start'=>array(),
            'success'=>array(),
            'run'=>array()
        );
    }

    function lift($containers_configuration) {
       $setup = is_file('.yoda.setup');
       foreach($containers_configuration as $container=>$config) {
            if(!$setup && $config['prompt']) {
                foreach($config['prompt'] as $read=>$question) {
                    $this->instructions['prompt'][] = 'echo "' . $question . '"';
                    $this->instructions['prompt'][] = 'read ' . $read;
                }
            }
            if(!$setup && $config['prompt_password']) {
                foreach($config['prompt_password'] as $read=>$question) {
                    $this->instructions['prompt'][] = 'echo "' . $question . '"';
                    $this->instructions['prompt'][] = 'read -s ' . $read;
                }
            }
            if(!$setup && $config['setup']) {
                foreach($config['setup'] as $command) {
                    $this->instructions['setup'][] = $command;
                }
            }
            if(!$setup && $config['setup_prompt']) {
                foreach($config['setup_prompt'] as $command) {
                    $this->instructions['setup_prompt'][] = $command;
                }
            }
            if($config['pull']) {
                if(is_bool($config['pull'])) {
                    $this->instructions['pull'][] = $this->docker->pull($config['image']);
                } else {
                    $config['pull'] = is_array($config['pull']) ? $config['pull'] : array($config['pull']);
                    foreach($config['pull'] as $pull) {
                        $this->instructions['pull'][] = $this->docker->pull($pull);
                    }
                    $this->instructions['pull'][] = $this->docker->pull($config['image']);
                }

            }
            if($config['build'] && is_string($config['build'])) {
                $this->instructions['build'][] = $this->docker->build($config['image'],  $config['build']);
            }

            // Stop the container
            $this->instructions['kill'][] = $this->docker->kill($config['name']);

            if($config['remove']) {
                $this->instructions['remove'][] = $this->docker->remove($config['name']);
                $this->instructions['run'][] = $this->docker->run($config['image'], $config['run']);
            } else {
                $this->instructions['start'][] = $this->docker->start($config['name']) . " || " . $this->docker->run($config['image'], $config['run']);
            }
        }
        return $this->instructions;
    }
}