<?php

require_once 'spyc.php';

class Parser {
	
	protected $variables = '/{([A-Za-z][A-Za-z0-9\-]*)}/';
	
	protected $blocks = '/{block:([A-Za-z][A-Za-z0-9]*)}(.*?){\/block:\\1}/s';
	
	public $data = array();
	
	public $type = '';
	
	public $defaults = array(
		'Favicon' 			=> 'http://assets.tumblr.com/images/default_avatar_16.gif',
		'PortraitURL-16' 	=> "http://assets.tumblr.com/images/default_avatar_16.gif",
		'PortraitURL-24' 	=> "http://assets.tumblr.com/images/default_avatar_24.gif",
		'PortraitURL-30' 	=> "http://assets.tumblr.com/images/default_avatar_30.gif",
		'PortraitURL-40' 	=> "http://assets.tumblr.com/images/default_avatar_40.gif",
		'PortraitURL-48' 	=> "http://assets.tumblr.com/images/default_avatar_48.gif",
		'PortraitURL-64' 	=> "http://assets.tumblr.com/images/default_avatar_64.gif",
		'PortraitURL-96' 	=> "http://assets.tumblr.com/images/default_avatar_96.gif",
		'PortraitURL-128' 	=> "http://assets.tumblr.com/images/default_avatar_128.gif",
		'CopyrightYears'	=> '2007-2010'
	);
	
	public $template = array();	
		
	public function __construct($data = array(), $type = 'index') {
		$this->type = $type;
		$this->template = array_merge($this->defaults, Spyc::YAMLLoad($data));
	}
	
	public function block_pattern($block_name) {
		return '/{block:('.$block_name.')}(.*?){\/block:\\1}/s';
	}
	
	public function parse($document) {
		// Do big GREP on $document based on page type
		// return $this->narrow_scope($document);
		// generate metatags.
		
		$doc = $this->get_posts($document);
		
		if ($this->template['Description']) {
			$doc = $this->render_block('Description', $doc);
		}
		
		// Finally, generate global values		
		return $this->seek($doc);
	}
	
	public function get_posts($document) {		
		$html = preg_replace_callback(
			$this->block_pattern('Posts'),
			array($this, 'render_posts'),
			$document
		);
		return $html;
	}
	
	public function render_posts($matches) {
		$block = $matches[2];
		$html = '';
		$posts = $this->template['Posts'];
		foreach ($posts as $index => $post) {
			//render non-post blocks
			$html .= $this->render_post($post, $this->filter_by_post_type($post, $block));
		}		
		return $html;
	}
	
	public function filter_by_post_type($post, $block) {
		$post_type = $this->block_pattern($post['Type']);
		$found = preg_match_all($post_type, $block, $posts);
		if ($found) {
			$split = preg_split($post_type, $block);
			$stripped = array();
			foreach ($split as $component) {
				$stripped[] = preg_replace($this->blocks, '', $component);
			}
			$html = implode(implode($posts[0]),$stripped);
			return $html;
		}
	}
		
	public function render_post($post, $block) {
		$html = '';
		switch($post['Type']) {
			case 'Text':
				$html = $this->render_text_post($post, $block);
				break;
		}
		return $html;
	}
	
	protected function render_text_post($post, $block) {
		$pattern = $this->block_pattern($post['Type']);

		$does_match = preg_match_all($pattern, $block, $posts);
		if ($does_match) {
			
			$html = '';
			foreach ($posts[2] as $index => $text) {
				$html = preg_replace('/{Body}/', $post['Body'], $text);
				if ($post['Title']) {
					$html = preg_replace('/{Title}/', $post['Title'], $html);
					$html = $this->render_block('Title', $html);
				} else {
					$html = preg_replace($this->block_pattern('Title'), '', $html);
				}
				$html = preg_replace($pattern, $html, $block, 1);	
			}
			return $html;
		}
	}
	
	public function render_block($name, $html) {
		return preg_replace_callback(
			$this->block_pattern($name),
			create_function(
				'$matches',
				'return $matches[2];'
			),
			$html
		);	
	}
	
	public function seek($context) {
		return preg_replace_callback($this->variables, array($this, 'convert_properties'), $context);
	}
	
	protected function convert_properties($match) {
		if (array_key_exists($match[1], $this->template)) {
			return $this->template[$match[1]];
		}
	}
	
	// public function narrow_scope($scope, $block='') {
	// 	$does_match = preg_match_all($this->blocks, $scope, $matcher);
	// 	$doc = '';
	// 			
	// 	if ($does_match){
	// 		foreach ($matcher[2] as $context) {
	// 			$doc .= $this->narrow_scope($context);
	// 		}
	// 		return $doc;
	// 	} else {
	// 		return $scope;
	// 		// return preg_replace($this->variables, $scope ,$scope);
	// 	}
	// }
	//
	
}


?>