<?php

namespace Tools\TestCase\View\Helper;

use Tools\View\Helper\TreeHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Configure;

class TreeHelperTest extends TestCase {

	public $fixtures = array('core.after_tree');

	public $Table;

	/**
	 * Initial Tree
	 *
	 * - One
	 * -- One-SubA
	 * - Two
	 * -- Two-SubA
	 * --- Two-SubA-1
	 * ---- Two-SubA-1-1
	 * - Three
	 * - Four
	 * -- Four-SubA
	 *
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('debug', true);

		$this->Tree = new TreeHelper(new View(null));
		$this->Table = TableRegistry::get('AfterTrees');
		$this->Table->addBehavior('Tree');

		//$this->Table->truncate();
		$connection = ConnectionManager::get('test');
		$sql = $this->Table->schema()->truncateSql($connection);
		foreach ($sql as $snippet) {
			$connection->execute($snippet);
		}
		//$this->Table->deleteAll(array());

		$data = array(
			array('name' => 'One'),
			array('name' => 'Two'),
			array('name' => 'Three'),
			array('name' => 'Four'),

			array('name' => 'One-SubA', 'parent_id' => 1),
			array('name' => 'Two-SubA', 'parent_id' => 2),
			array('name' => 'Four-SubA', 'parent_id' => 4),

			array('name' => 'Two-SubA-1', 'parent_id' => 6),

			array('name' => 'Two-SubA-1-1', 'parent_id' => 8),
		);
		foreach ($data as $row) {
			$row = new Entity($row);
			$this->Table->save($row);
		}
	}

	public function tearDown() {
		unset($this->Table);

 		TableRegistry::clear();
		parent::tearDown();
	}

	public function testObject() {
		$this->assertInstanceOf('Tools\View\Helper\TreeHelper', $this->Tree);
	}

	public function testGenerate() {
		$tree = $this->Table->find('threaded')->toArray();

		$output = $this->Tree->generate($tree);

		$expected = <<<TEXT

<ul>
	<li>One
	<ul>
		<li>One-SubA</li>
	</ul>
	</li>
	<li>Two
	<ul>
		<li>Two-SubA
		<ul>
			<li>Two-SubA-1
			<ul>
				<li>Two-SubA-1-1</li>
			</ul>
			</li>
		</ul>
		</li>
	</ul>
	</li>
	<li>Three</li>
	<li>Four
	<ul>
		<li>Four-SubA</li>
	</ul>
	</li>
</ul>

TEXT;
		$this->assertTextEquals($expected, $output);
		$this->assertTrue(substr_count($output, '<ul>') === substr_count($output, '</ul>'));
		$this->assertTrue(substr_count($output, '<li>') === substr_count($output, '</li>'));
	}

	/**
	 * TreeHelperTest::testGenerateWithFindAll()
	 *
	 * @return void
	 */
	public function testGenerateWithFindAll() {
		$tree = $this->Table->find('all', array('order' => array('lft' => 'ASC')))->toArray();

		$output = $this->Tree->generate($tree);
		//debug($output); return;
		$expected = <<<TEXT

<ul>
	<li>One
	<ul>
		<li>One-SubA</li>
	</ul>
	</li>
	<li>Two
	<ul>
		<li>Two-SubA
		<ul>
			<li>Two-SubA-1
			<ul>
				<li>Two-SubA-1-1</li>
			</ul>
			</li>
		</ul>
		</li>
	</ul>
	</li>
	<li>Three</li>
	<li>Four
	<ul>
		<li>Four-SubA</li>
	</ul>
	</li>
</ul>

TEXT;
		$output = str_replace(array("\t", "\r", "\n"), '', $output);
		$expected = str_replace(array("\t", "\r", "\n"), '', $expected);
		$this->assertTextEquals($expected, $output);
	}

	public function testGenerateWithDepth() {
		$tree = $this->Table->find('threaded')->toArray();

		$output = $this->Tree->generate($tree, array('depth' => 1));
		$expected = <<<TEXT

	<ul>
		<li>One
		<ul>
			<li>One-SubA</li>
		</ul>
		</li>
		<li>Two
		<ul>
			<li>Two-SubA
			<ul>
				<li>Two-SubA-1
				<ul>
					<li>Two-SubA-1-1</li>
				</ul>
				</li>
			</ul>
			</li>
		</ul>
		</li>
		<li>Three</li>
		<li>Four
		<ul>
			<li>Four-SubA</li>
		</ul>
		</li>
	</ul>

TEXT;
		$this->assertTextEquals($expected, $output);
	}

	public function testGenerateWithSettings() {
		$tree = $this->Table->find('threaded')->toArray();

		$output = $this->Tree->generate($tree, array('class' => 'navi', 'id' => 'main', 'type' => 'ol'));
		$expected = <<<TEXT

<ol class="navi" id="main">
	<li>One
	<ol>
		<li>One-SubA</li>
	</ol>
	</li>
	<li>Two
	<ol>
		<li>Two-SubA
		<ol>
			<li>Two-SubA-1
			<ol>
				<li>Two-SubA-1-1</li>
			</ol>
			</li>
		</ol>
		</li>
	</ol>
	</li>
	<li>Three</li>
	<li>Four
	<ol>
		<li>Four-SubA</li>
	</ol>
	</li>
</ol>

TEXT;
		$this->assertTextEquals($expected, $output);
	}

	public function testGenerateWithMaxDepth() {
		$tree = $this->Table->find('threaded')->toArray();

		$output = $this->Tree->generate($tree, array('maxDepth' => 2));
		$expected = <<<TEXT

<ul>
	<li>One
	<ul>
		<li>One-SubA</li>
	</ul>
	</li>
	<li>Two
	<ul>
		<li>Two-SubA
		<ul>
			<li>Two-SubA-1</li>
		</ul>
		</li>
	</ul>
	</li>
	<li>Three</li>
	<li>Four
	<ul>
		<li>Four-SubA</li>
	</ul>
	</li>
</ul>

TEXT;
		$this->assertTextEquals($expected, $output);
	}

	public function testGenerateWithAutoPath() {
		$tree = $this->Table->find('threaded')->toArray();
		//debug($tree);

		$output = $this->Tree->generate($tree, array('autoPath' => array(7, 10))); // Two-SubA-1
		//debug($output);
		$expected = <<<TEXT

<ul>
	<li>One
	<ul>
		<li>One-SubA</li>
	</ul>
	</li>
	<li class="active">Two
	<ul>
		<li class="active">Two-SubA
		<ul>
			<li class="active">Two-SubA-1
			<ul>
				<li>Two-SubA-1-1</li>
			</ul>
			</li>
		</ul>
		</li>
	</ul>
	</li>
	<li>Three</li>
	<li>Four
	<ul>
		<li>Four-SubA</li>
	</ul>
	</li>
</ul>

TEXT;
		$this->assertTextEquals($expected, $output);

		$output = $this->Tree->generate($tree, array('autoPath' => array(8, 9))); // Two-SubA-1-1
		//debug($output);
		$expected = <<<TEXT

<ul>
	<li>One
	<ul>
		<li>One-SubA</li>
	</ul>
	</li>
	<li class="active">Two
	<ul>
		<li class="active">Two-SubA
		<ul>
			<li class="active">Two-SubA-1
			<ul>
				<li class="active">Two-SubA-1-1</li>
			</ul>
			</li>
		</ul>
		</li>
	</ul>
	</li>
	<li>Three</li>
	<li>Four
	<ul>
		<li>Four-SubA</li>
	</ul>
	</li>
</ul>

TEXT;
		$this->assertTextEquals($expected, $output);
	}

	/**
	 *
	 * - One
	 * -- One-SubA
	 * - Two
	 * -- Two-SubA
	 * --- Two-SubA-1
	 * ---- Two-SubA-1-1
	 * -- Two-SubB
	 * -- Two-SubC
	 * - Three
	 * - Four
	 * -- Four-SubA
	 */
	public function testGenerateWithAutoPathAndHideUnrelated() {
		$this->skipIf(true, 'FIXME');

		$data = array(
			array('name' => 'Two-SubB', 'parent_id' => 2),
			array('name' => 'Two-SubC', 'parent_id' => 2),
		);
		foreach ($data as $row) {
			$row = new Entity($row);
			$this->Table->save($row);
		}

		$tree = $this->Table->find('threaded')->toArray();
		$id = 6;
		$nodes = $this->Table->find('path', array('for' => $id));
		$path = $nodes->extract('id')->toArray();

		$output = $this->Tree->generate($tree, array('autoPath' => array(6, 11), 'hideUnrelated' => true, 'treePath' => $path, 'callback' => array($this, '_myCallback'))); // Two-SubA
		//debug($output);

		$expected = <<<TEXT

<ul>
	<li>One</li>
	<li class="active">Two (active)
	<ul>
		<li class="active">Two-SubA (active)
		<ul>
			<li>Two-SubA-1</li>
		</ul>
		</li>
		<li>Two-SubB</li>
		<li>Two-SubC</li>
	</ul>
	</li>
	<li>Three</li>
	<li>Four</li>
</ul>

TEXT;
		$output = str_replace(array("\t"), '', $output);
		$expected = str_replace(array("\t"), '', $expected);
		$this->assertTextEquals($expected, $output);
	}

	/**
	 *
	 * - One
	 * -- One-SubA
	 * - Two
	 * -- Two-SubA
	 * --- Two-SubA-1
	 * ---- Two-SubA-1-1
	 * -- Two-SubB
	 * -- Two-SubC
	 * - Three
	 * - Four
	 * -- Four-SubA
	 */
	public function testGenerateWithAutoPathAndHideUnrelatedAndSiblings() {
		$this->skipIf(true, 'FIXME');

		$data = array(
			array('name' => 'Two-SubB', 'parent_id' => 2),
			array('name' => 'Two-SubC', 'parent_id' => 2),
		);
		foreach ($data as $row) {
			$row = new Entity($row);
			$this->Table->save($row);
		}

		$tree = $this->Table->find('threaded')->toArray();
		$id = 6;
		$nodes = $this->Table->find('path', array('for' => $id));
		$path = $nodes->extract('id')->toArray();

		$output = $this->Tree->generate($tree, array(
			'autoPath' => array(6, 11), 'hideUnrelated' => true, 'treePath' => $path,
			'callback' => array($this, '_myCallbackSiblings'))); // Two-SubA
		//debug($output);

		$expected = <<<TEXT

<ul>
	<li>One (sibling)</li>
	<li class="active">Two (active)
	<ul>
		<li class="active">Two-SubA (active)
		<ul>
			<li>Two-SubA-1</li>
		</ul>
		</li>
		<li>Two-SubB</li>
		<li>Two-SubC</li>
	</ul>
	</li>
	<li>Three (sibling)</li>
	<li>Four (sibling)</li>
</ul>

TEXT;
		$output = str_replace(array("\t", "\r", "\n"), '', $output);
		$expected = str_replace(array("\t", "\r", "\n"), '', $expected);
		//debug($output);
		//debug($expected);
		$this->assertTextEquals($expected, $output);
	}

	public function _myCallback($data) {
		if (!empty($data['data']['hide'])) {
			return;
		}
		return $data['data']['name'] . ($data['activePathElement'] ? ' (active)' : '');
	}

	public function _myCallbackSiblings($data) {
		if (!empty($data['data']['hide'])) {
			return;
		}
		if ($data['depth'] == 0 && $data['isSibling']) {
			return $data['data']['name'] . ' (sibling)';
		}
		return $data['data']['name'] . ($data['activePathElement'] ? ' (active)' : '');
	}

	/**
	 * TreeHelperTest::testGenerateProductive()
	 *
	 * @return void
	 */
	public function testGenerateProductive() {
		$tree = $this->Table->find('threaded')->toArray();

		$output = $this->Tree->generate($tree, array('indent' => false));
		$expected = '<ul><li>One<ul><li>One-SubA</li></ul></li><li>Two<ul><li>Two-SubA<ul><li>Two-SubA-1<ul><li>Two-SubA-1-1</li></ul></li></ul></li></ul></li><li>Three</li><li>Four<ul><li>Four-SubA</li></ul></li></ul>';

		$this->assertTextEquals($expected, $output);
	}

}
