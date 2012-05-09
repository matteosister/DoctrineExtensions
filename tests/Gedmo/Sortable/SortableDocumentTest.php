<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseMongoODM;
use Sortable\Fixture\Document\Node;
use Sortable\Fixture\Item;
use Sortable\Fixture\Category;
use Sortable\Fixture\SimpleListItem;
use Sortable\Fixture\Author;
use Sortable\Fixture\Paper;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableDocumentTest extends BaseTestCaseMongoODM
{
    const NODE = 'Sortable\\Fixture\\Node';
    const ITEM = 'Sortable\\Fixture\\Item';
    const CATEGORY = 'Sortable\\Fixture\\Category';
    const SIMPLE_LIST_ITEM = 'Sortable\\Fixture\\SimpleListItem';
    const AUTHOR = 'Sortable\\Fixture\\Author';
    const PAPER = 'Sortable\\Fixture\\Paper';

    private $nodeId;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SortableListener);

        $this->getMockDocumentManager($evm);
        //$this->startQueryLog();

        $this->populate();
    }

    protected function tearDown()
    {
        //$this->stopQueryLog();
    }

    /**
     * @test
     */
    public function shouldSetSortPositionToInsertedNode()
    {
        $node = $this->dm->find(self::NODE, $this->nodeId);
        $this->assertEquals(0, $node->getPosition());
    }

    /**
     * @test
     */
    public function shouldSortManyNewNodes()
    {
        for ($i = 2; $i <= 10; $i++) {
            $node = new Node();
            $node->setName("Node".$i);
            $node->setPath("/");
            $this->dm->persist($node);
        }
        $this->dm->flush();

        $dql = 'SELECT node FROM '.self::NODE.' node';
        $dql .= ' WHERE node.path = :path ORDER BY node.position';
        $nodes = $this->dm
            ->createQuery($dql)
            ->setParameter('path', '/')
            ->getResult()
        ;

        $this->assertCount(10, $nodes);
        $this->assertEquals('Node1', $nodes[0]->getName());
        $this->assertEquals(2, $nodes[2]->getPosition());
    }

    /**
     * @test
     */
    public function shouldShiftPositionForward()
    {
        $node2 = new Node();
        $node2->setName("Node2");
        $node2->setPath("/");
        $this->dm->persist($node2);

        $node = new Node();
        $node->setName("Node3");
        $node->setPath("/");
        $this->dm->persist($node);

        $node = new Node();
        $node->setName("Node4");
        $node->setPath("/");
        $this->dm->persist($node);

        $node = new Node();
        $node->setName("Node5");
        $node->setPath("/");
        $this->dm->persist($node);

        $this->dm->flush();

        $this->assertEquals(1, $node2->getPosition());
        $node2->setPosition(3);
        $this->dm->persist($node2);
        $this->dm->flush();

        $repo = $this->dm->getRepository(self::NODE);
        $nodes = $repo->getBySortableGroups(array('path' => '/'));

        $this->assertEquals('Node1', $nodes[0]->getName());
        $this->assertEquals('Node3', $nodes[1]->getName());
        $this->assertEquals('Node4', $nodes[2]->getName());
        $this->assertEquals('Node2', $nodes[3]->getName());
        $this->assertEquals('Node5', $nodes[4]->getName());
    }

    /**
     * @test
     */
    public function shouldShiftPositionBackward()
    {
        $node = new Node();
        $node->setName("Node2");
        $node->setPath("/");
        $this->dm->persist($node);

        $node = new Node();
        $node->setName("Node3");
        $node->setPath("/");
        $this->dm->persist($node);

        $node2 = new Node();
        $node2->setName("Node4");
        $node2->setPath("/");
        $this->dm->persist($node2);

        $node = new Node();
        $node->setName("Node5");
        $node->setPath("/");
        $this->dm->persist($node);

        $this->dm->flush();
        $this->assertEquals(3, $node2->getPosition());


        $node2->setPosition(1);
        $this->dm->persist($node2);
        $this->dm->flush();

        $repo = $this->dm->getRepository(self::NODE);
        $nodes = $repo->getBySortableGroups(array('path' => '/'));

        $this->assertEquals('Node1', $nodes[0]->getName());
        $this->assertEquals('Node4', $nodes[1]->getName());
        $this->assertEquals('Node2', $nodes[2]->getName());
        $this->assertEquals('Node3', $nodes[3]->getName());
        $this->assertEquals('Node5', $nodes[4]->getName());
    }

    /**
     * @test
     */
    public function shouldSyncPositionAfterDelete()
    {
        $repo = $this->dm->getRepository(self::NODE);

        $node2 = new Node();
        $node2->setName("Node2");
        $node2->setPath("/");
        $this->dm->persist($node2);

        $node3 = new Node();
        $node3->setName("Node3");
        $node3->setPath("/");
        $this->dm->persist($node3);

        $this->dm->flush();

        $node1 = $repo->findOneByName('Node1');
        $this->dm->remove($node2);
        $this->dm->flush();

        $this->assertEquals(0, $node1->getPosition());
        $this->assertEquals(1, $node3->getPosition());
    }

    /**
     * test
     */
    public function shouldGroupByAssociation()
    {
        $category1 = new Category();
        $category1->setName("Category1");
        $this->dm->persist($category1);
        $category2 = new Category();
        $category2->setName("Category2");
        $this->dm->persist($category2);
        $this->dm->flush();

        $item3 = new Item();
        $item3->setName("Item3");
        $item3->setCategory($category1);
        $this->dm->persist($item3);

        $item4 = new Item();
        $item4->setName("Item4");
        $item4->setCategory($category1);
        $this->dm->persist($item4);

        $this->dm->flush();

        $item1 = new Item();
        $item1->setName("Item1");
        $item1->setPosition(0);
        $item1->setCategory($category1);
        $this->dm->persist($item1);

        $item2 = new Item();
        $item2->setName("Item2");
        $item2->setPosition(0);
        $item2->setCategory($category1);
        $this->dm->persist($item2);

        $item2 = new Item();
        $item2->setName("Item2_2");
        $item2->setPosition(0);
        $item2->setCategory($category2);
        $this->dm->persist($item2);
        $this->dm->flush();

        $item1 = new Item();
        $item1->setName("Item1_2");
        $item1->setPosition(0);
        $item1->setCategory($category2);
        $this->dm->persist($item1);
        $this->dm->flush();

        $repo = $this->dm->getRepository(self::CATEGORY);
        $category1 = $repo->findOneByName('Category1');
        $category2 = $repo->findOneByName('Category2');

        $repo = $this->dm->getRepository(self::ITEM);

        $items = $repo->getBySortableGroups(array('category' => $category1));

        $this->assertEquals("Item1", $items[0]->getName());
        $this->assertEquals("Category1", $items[0]->getCategory()->getName());

        $this->assertEquals("Item2", $items[1]->getName());
        $this->assertEquals("Category1", $items[1]->getCategory()->getName());

        $this->assertEquals("Item3", $items[2]->getName());
        $this->assertEquals("Category1", $items[2]->getCategory()->getName());

        $this->assertEquals("Item4", $items[3]->getName());
        $this->assertEquals("Category1", $items[3]->getCategory()->getName());

        $items = $repo->getBySortableGroups(array('category' => $category2));

        $this->assertEquals("Item1_2", $items[0]->getName());
        $this->assertEquals("Category2", $items[0]->getCategory()->getName());

        $this->assertEquals("Item2_2", $items[1]->getName());
        $this->assertEquals("Category2", $items[1]->getCategory()->getName());
    }

    /**
     * @test
     */
    public function shouldFixIssue219()
    {
        $item1 = new SimpleListItem();
        $item1->setName("Item 1");
        $this->dm->persist($item1);

        $this->dm->flush();

        $item1->setName("Update...");
        $item1->setPosition(1);
        $this->dm->persist($item1);
        $this->dm->flush();

        $this->dm->remove($item1);
        $this->dm->flush();
    }

    /**
     * @test
     */
    public function shouldFixIssue226()
    {
        $paper1 = new Paper();
        $paper1->setName("Paper1");
        $this->dm->persist($paper1);

        $paper2 = new Paper();
        $paper2->setName("Paper2");
        $this->dm->persist($paper2);

        $author1 = new Author();
        $author1->setName("Author1");
        $author1->setPaper($paper1);

        $author2 = new Author();
        $author2->setName("Author2");
        $author2->setPaper($paper1);

        $author3 = new Author();
        $author3->setName("Author3");
        $author3->setPaper($paper2);

        $this->dm->persist($author1);
        $this->dm->persist($author2);
        $this->dm->persist($author3);
        $this->dm->flush();

        $this->assertEquals(1, $author1->getPosition());
        $this->assertEquals(2, $author2->getPosition());
        $this->assertEquals(1, $author3->getPosition());
    }

    /**
     * @test
     */
    function shouldFixIssue275()
    {
        $nodes = array();
        for ($i = 2; $i <= 10; $i++) {
            $node = new Node();
            $node->setName("Node".$i);
            $node->setPath("/");
            $this->dm->persist($node);
            $nodes[] = $node;
        }
        $this->dm->flush();

        $node1 = $this->dm->find(self::NODE, $this->nodeId);
        $this->dm->remove($node1);
        $this->dm->flush();

        for ($i = 1; $i <= 9; $i++) {
            $nodes[$i-1]->setPosition($i);
            $this->dm->persist($nodes[$i-1]);
        }
        $this->dm->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::NODE,
            self::ITEM,
            self::CATEGORY,
            self::SIMPLE_LIST_ITEM,
            self::AUTHOR,
            self::PAPER,
        );
    }

    private function populate()
    {
        $node = new Node();
        $node->setName("Node1");
        $node->setPath("/");

        $this->dm->persist($node);
        $this->dm->flush();
        $this->nodeId = $node->getId();
    }
}
