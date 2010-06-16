<?

/**
 * Simple paginator. Preserves GET parameters.
 **/
	
class Paginator {

	private $pages;
	private $page;
	private $items_per_page; 
	private $links;
	private $base_path;
	private $total_page_links_to_show; 

	function __construct($pages, $items_per_page, $total_page_links_to_show = 7) {
		$this->pages = $pages;
		$this->items_per_page = $items_per_page;
		$this->links = array();
		$parsed_request_uri = parse_url($_SERVER['REQUEST_URI']);
    $this->basePath = $parsed_request_uri['path'];
    $this->total_page_links_to_show = $total_page_links_to_show;
	}

	public function paginate() {
    $links = $this->get_links();
		$links_html = '<ol>';
		foreach($links as $link) {
			$class_html = ($link['class'] != '') ? "class=\"{$link['class']}\"" : '';
			if ($link['class'] == 'current') {
				$link_html = "<li $class_html>{$link['label']}</li>\n";
			} else {
				$link_html = "<li $class_html><a href=\"{$link['href']}\">{$link['label']}</a></li>\n";
			}
			$links_html .= $link_html;
		}
		$links_html .= "</ol>";
		
		echo $links_html;
	}

	private function add_link($label, $params, $class= '') {
    $split_path = split('/', $this->base_path);
    foreach($split_path as &$path_item) {
      $path_item = str_replace('+', '%20', urlencode($path_item));
    }
    $path = join($split_path, '/');
		$this->links[] = array('label' => $label, 'href' => $path . build_query_string($params), 'class' => $class);
	}
	
	private function get_links() {		
		$this->page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
		if ($this->page === false || $this->page < 1) {
		  $this->page = 1;
		}

		// determine which numeric links to display
		if ($this->pages != 1) {
			if ($this->pages < $this->total_page_links_to_show) {
				// show pages 1 to <total num pages> since we have less than <max num pages to show> pages
				$start_page = 1;
				$end_page = $this->pages;
				$add_first = false;
				$add_last = false;
			} elseif ($this->page < $this->total_page_links_to_show) {
				// show from pages 1 to <max num pages to show> since we are in that window 
			 	$start_page = 1;
				$end_page = $this->total_page_links_to_show;
				$add_first = false;
				$add_last = true;
			} else {
				// center the current page in a window of width <max num pages to show> pages
				$half_window = floor($this->total_page_links_to_show / 2);
				$start_page = $this->page - $half_window;
				$end_page = $this->page + $half_window;
				if ($start_page < 1) {
					$shift = abs($start_page) + 1;
					$start_page += $shift;
					$end_page += $shift;
				} elseif ($end_page > $this->pages) {
					$shift = $end_page - $this->pages;
					$start_page -= $shift;
					$end_page -= $shift;
				}	
				if ($start_page < 1) {
					$start_page = 1;
				}
				$add_first = true;
				$add_last =  ($this->page < ($this->pages - $this->total_page_links_to_show));
			}
		}
		
		// add previous and first links if we're not on the first page
		if ($this->page > 1) {
			$params = $_GET;
			if ($add_first && (($this->page) >= 2)) { 
				// add a first link if we're on page 2 or higher
				$params['page'] = 1;
				$this->add_link('1', $params, 'first');
			}
			$params['page'] = $this->page - 1;
			$this->add_link('&laquo prev', $params, 'prev');
		}
		
		
		// add the page number links
		if ($this->pages != 1) {
			$params = $_GET;
			for ($i = $start_page; $i <= $end_page; $i++) {
				$params['page'] = $i;
				$class = ($i == $this->page) ? 'current' : '';  // add current class for active page
				$this->add_link("$i", $params, $class);
			}
		}
		
		// add next and last links if we're not on the last page
		if ($this->page < $this->pages) {
			$params = $_GET;
			$params['page'] = $this->page + 1;
			$this->add_link('next &raquo;', $params, 'next');
		}

		return $this->links;
	}

}
?>