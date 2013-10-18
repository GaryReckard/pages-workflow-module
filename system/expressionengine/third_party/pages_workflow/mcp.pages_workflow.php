<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Pages Workflow Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		RBC
 * @link		
 */

class Pages_workflow_mcp {
	
	public $return_data;	
	private $_base_url;	
	var $page_array	= array();
	var $pages = array();
	var $closed_pages = array();
	var $homepage_display;	
	

	function Pages_workflow_mcp($switch=TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		$this->EE->load->model('pages_model');
		$this->_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages_workflow';
		
		$query = $this->EE->pages_model->fetch_configuration();

		$default_channel = 0;

		$this->homepage_display = 'not_nested';		

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$$row['configuration_name'] = $row['configuration_value'];
			}
			
			$this->homepage_display = $homepage_display;
		}

        $new_page_location = '';
        
		if ($default_channel != 0)
		{
			$new_page_location = AMP.'M=entry_form'.AMP.'channel_id='.$default_channel;
		}		
		
		$this->EE->cp->set_right_nav(array(
				'hide_wip' => '#',
				'hide_completed' => '#',
				'hide_approved' => '#',
			));
	}

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		
		$this->EE->load->model('pages_model');
		
		$this->EE->cp->set_variable('cp_page_title', 
								lang('pages_workflow_module_name'));
								
		$this->EE->load->library('table');
		$this->EE->cp->add_to_head("<link rel='stylesheet' href='/themes/third_party/pages_workflow/css/pages_workflow.css'>");
		
		$this->EE->load->library('javascript');
		$this->EE->javascript->output(array('
		
			var wipBtn = {
				class: ".workinprogress",
				selector: $(".button>:contains(Hide Works in Progress)"),
				name: "Work in Progress"
			};
	
			var completedBtn = {
				class: ".completed",			
				selector: $(".button>:contains(Hide Completed)"),
				name: "Completed"	
			};
			
			var approvedBtn = {
				class: ".approved",		
				selector: $(".button>:contains(Hide Approved)"),
				name: "Approved"	
			};
			
			var buttons = [wipBtn, completedBtn, approvedBtn];
			
			//initialize buttons
			buttons.forEach(function(button) {
				
				button.hidden = false;
				
				button.hide = function() {
					if (button.hidden === false) {
						$(button.class).parents("tr").slideUp(function() {
							button.hidden = true;
							button.selector.html("Show " + button.name);							
						});
					} else {
						$(button.class).parents("tr").slideDown(function() {
							button.hidden = false;
							button.selector.html("Hide " + button.name);
						});					
					}		
				},
				
				button.selector.click(function() {
					button.hide();
				})
			})

		'));

		$this->EE->javascript->compile();
		
		$completion_statuses = $this->getCompletionStatuses();
		$closed_pages = $this->getClosedPages();
										
		$pages = $this->EE->config->item('site_pages');
		//die("<pre>".print_r($pages,true)."</pre>");	
				
		natcasesort($pages[$this->EE->config->item('site_id')]['uris']);
		$vars['pages'] = array();
		
		$page_title_map = $this->getPageTitles();
		
		//  Our Pages
		$i = 0;
		$previous = array();
		$spcr = '<img src="'.PATH_CP_GBL_IMG.'clear.gif" border="0"  width="24" height="14" alt="" title="" />';
		$indent = $spcr.'<img src="'.PATH_CP_GBL_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" />';

		foreach($pages[$this->EE->config->item('site_id')]['uris'] as $entry_id => $url)
		{
			
			//if page status is 'closed' move on to the next one
			if (in_array($entry_id, $closed_pages)) continue;
			
			//echo "TEST".$entry_id."<br";
			$url = ($url == '/') ? '/' : '/'.trim($url, '/');

			$vars['pages'][$entry_id]['entry_id'] = $entry_id;
			$vars['pages'][$entry_id]['entry_id'] = $entry_id;
			$vars['pages'][$entry_id]['view_url'] = $this->EE->functions->fetch_site_index().QUERY_MARKER.'URL='.urlencode($this->EE->functions->create_url($url));
			$vars['pages'][$entry_id]['page'] = $url;
			$vars['pages'][$entry_id]['indent'] = '';

			if ($this->homepage_display == 'nested' && $url != '/')
            {
            	$x = explode('/', trim($url, '/'));

            	for($i=0, $s=count($x); $i < $s; ++$i)
            	{
            		if (isset($previous[$i]) && $previous[$i] == $x[$i])
            		{
            			continue;
            		}

					$this_indent = ($i == 0) ? '' : str_repeat($spcr, $i-1).$indent;
					$vars['pages'][$entry_id]['indent'] = $this_indent;
            	}

            	$previous = $x;
            }

			$vars['pages'][$entry_id]['toggle'] = array(
														'name'		=> 'toggle[]',
														'id'		=> 'delete_box_'.$entry_id,
														'value'		=> $entry_id,
														'class'		=>'toggle'
														);
														
			//set page title
			$vars['pages'][$entry_id]['title'] = $page_title_map[$entry_id];
			
			//set completion status if set
			if ($completion_statuses[$entry_id] != "") {
				$vars['pages'][$entry_id]['completion_status'] = $completion_statuses[$entry_id];
			}
			//default to WIP
			else {
				$vars['pages'][$entry_id]['completion_status'] = 'Work in Progress';
			}

		}		
		
		return $this->EE->load->view('index.php', $vars, TRUE);
	}

	//returns useful array of closed pages ids
	public function getClosedPages() {
		
		$return_array = array();
		
		$this->EE->db->select('entry_id');
		$this->EE->db->from('exp_channel_titles');
		$this->EE->db->where('status','closed');		
		$query = $this->EE->db->get();
		$results = $query->result_array();
		foreach($results as $result) {
			$entry_id = $result['entry_id'];
			array_push($return_array, $entry_id);
		}
		return $return_array;		
	}

	public function getCompletionStatuses() {
		
		$return_array = array();
		$channel_map = $this->getChannelMap();
		$page_channel_id = $channel_map['page'];
		$field_map = $this->getChannelFieldMap();

        //make sure field exists 
		if (!array_key_exists('page_completion_status', $field_map)) {
            die("Does not compute! You need to create a page channel field called page_completion_status");
        }

		$completion_status_id = $field_map['page_completion_status'];
		$completion_id_string = 'field_id_'.$completion_status_id;
		
		$this->EE->db->select('entry_id, '.$completion_id_string);
		$this->EE->db->from('exp_channel_data');
		$this->EE->db->where('site_id',$page_channel_id);		
		$query = $this->EE->db->get();
		$results = $query->result_array();
		
		foreach($results as $result) {
			$entry_id = $result['entry_id'];
			$completion_status = $result[$completion_id_string];
			$return_array[$entry_id] = $completion_status;
		}
		
		return $return_array;	
	}

	//returns array of channel_name to channel_id
	public function getChannelMap() {
		
		$return_array = array();
		
		$this->EE->db->select('channel_id, channel_name');
		$this->EE->db->from('exp_channels');
		$query = $this->EE->db->get();
		$results = $query->result_array();
		
		foreach($results as $result) {
			$return_array[$result['channel_name']] = $result['channel_id'];
		}
		
		return $return_array;
	}

	//returns array of field_name to field_id
	public function getChannelFieldMap() {
		
		$return_array = array();
		
		$this->EE->db->select('field_id, field_name');
		$this->EE->db->from('exp_channel_fields');
		$query = $this->EE->db->get();
		$results = $query->result_array();
		
		foreach($results as $result) {
			$return_array[$result['field_name']] = $result['field_id'];
		}
		
		return $return_array;
	}
	
	//returns an array mapping entry_id to "Non-Programmer Human-Friendly Title Like This"
	public function getPageTitles() {
		
		$result_map = array();	
		$this->EE->db->select('entry_id, title');
		$this->EE->db->from('exp_channel_titles');
		$this->EE->db->where('site_id',1);		
		$query = $this->EE->db->get();

		$results = $query->result_array();
		foreach ($results as $result) {
			$id = $result['entry_id'];
			$title = $result['title'];
			$result_map[$id]=$title;
		}
		//die("<pre>".print_r($result_map,true)."</pre>");
		return $result_map;
	}	

}
/* End of file mcp.pages_workflow.php */
/* Location: /system/expressionengine/third_party/pages_workflow/mcp.pages_workflow.php */
