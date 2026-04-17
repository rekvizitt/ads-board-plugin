 <?php
 /**
  * Register all actions and filters for the plugin.
  *
  * Maintain a list of all hooks that are registered throughout
  * the plugin, and register them with the WordPress API. Call the
  * run function to execute the list of actions and filters.
  *
  * @since      0.0.1
  * @package    Ads_Board
  * @subpackage Ads_Board/includes
  * @author     Vladislav Chekaviy
  */
 if (!defined("ABSPATH")) {
     exit();
 }

 class Ads_Loader
 {
     protected $actions = [];
     protected $filters = [];

     public function add_action(
         $hook,
         $component,
         $callback,
         $priority = 10,
         $accepted_args = 1,
     ) {
         $this->actions = $this->add(
             $this->actions,
             $hook,
             $component,
             $callback,
             $priority,
             $accepted_args,
         );
         return $this;
     }

     public function add_filter(
         $hook,
         $component,
         $callback,
         $priority = 10,
         $accepted_args = 1,
     ) {
         $this->filters = $this->add(
             $this->filters,
             $hook,
             $component,
             $callback,
             $priority,
             $accepted_args,
         );
         return $this;
     }

     public function run()
     {
         // Фильтры должны идти ПЕРВЫМИ (query_vars парсится до template_redirect)
         foreach ($this->filters as $hook) {
             add_filter(
                 $hook["hook"],
                 [$hook["component"], $hook["callback"]],
                 $hook["priority"],
                 $hook["accepted_args"],
             );
         }
         foreach ($this->actions as $hook) {
             add_action(
                 $hook["hook"],
                 [$hook["component"], $hook["callback"]],
                 $hook["priority"],
                 $hook["accepted_args"],
             );
         }
     }

     private function add(
         $hooks,
         $hook,
         $component,
         $callback,
         $priority,
         $accepted_args,
     ) {
         $hooks[] = [
             "hook" => $hook,
             "component" => $component,
             "callback" => $callback,
             "priority" => $priority,
             "accepted_args" => $accepted_args,
         ];
         return $hooks;
     }
 }

