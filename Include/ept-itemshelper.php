<?php

class ItemsHelper {

	private $items;

	public function __construct($items) {
		$this->items = $items;
	}

	public function htmlList($selected = '') {
		return $this->htmlFromArray($this->itemArray(), $selected);
	}

	private function itemArray() {
		$result = array();
		foreach($this->items as $item) {
			if ($item->parent == 0) {
				$result[$item->term_id]['term_id'] = $item->term_id;
				$result[$item->term_id]['name'] = $item->name;
				$result[$item->term_id]['children'] = $this->itemWithChildren($item);
			}
		}
		// var_dump($result);
		return $result;
	}

	private function childrenOf($item) {
		$result = array();
		foreach($this->items as $i) {
			if ($i->parent == $item->term_id) {
				$result[] = $i;
			}
		}
		return $result;
	}

	private function itemWithChildren($item) {
		$result = array();
		$children = $this->childrenOf($item);
		foreach ($children as $child) {
			$result[$child->term_id]['term_id'] = $child->term_id;
			$result[$child->term_id]['name'] = $child->name;
			$result[$child->term_id]['children'] = $this->itemWithChildren($child);
		}
		return $result;
	}

	private function htmlFromArray($array, $selected) {
		$html = '';
		foreach($array as $k => $v) {
			$checked_array = explode( ',', $selected );
			$is_checked = '';
			if (in_array($v['term_id'], $checked_array)) {
	            $is_checked = 'checked="checked"';
	        }
			$html .= "<ul>";
			$html .= "<li>";
			$html .= "<label class='checkbox'>";
			$html .= "<input type='checkbox' name='ept_cat_tweet[]' value='". $v['term_id'] ."' " . $is_checked . ">";
			$html .= $v['name'];
			$html .= "</label>";
			$html .= "</li>";
			if(count($v['children']) > 0) {
				$html .= $this->htmlFromArray($v['children'], $selected);
			}
			$html .= "</ul>";
		}
		return $html;
	}
}