<?php
namespace Slub\SlubEvents\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2014 Alexander Bigga <typo3@slub-dresden.de>, SLUB Dresden
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * @package slub_events
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CategoryRepository extends Repository
{
    // Order by BE sorting
    protected $defaultOrderings = array(

        'sorting' => QueryInterface::ORDER_ASCENDING

    );

    /**
     * Finds all datasets by MM relation categories
     *
     * @param string $categories separated by comma
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllByUids($categories)
    {
        $query = $this->createQuery();

        // we have to ignore sys_language here
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $constraints = [];
        $constraints[] = $query->in('uid', $categories);

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        return $query->execute();
    }

    /**
     * Finds all datasets and return in tree order
     *
     * @return array The found Category Objects
     */
    public function findAllTree()
    {
        $categories = $this->findAll();

        $flatCategories = [];
        foreach ($categories as $category) {
            $flatCategories[$category->getUid()] = [
                'item'   => $category,
                'parent' => ($category->getParent()->current()) ? $category->getParent()->current()->getUid() : null,
            ];
        }

        $tree = [];
        foreach ($flatCategories as $id => &$node) {
            if ($node['parent'] === null) {
                $tree[$id] = &$node;
            } else {
                $flatCategories[$node['parent']]['children'][$id] = &$node;
            }
        }

        return $tree;
    }

    /**
     * Finds all datasets and return in tree order
     *
     * @param string $categories separated by comma
     *
     * @return array The found Category Objects
     */
    public function findAllByUidsTree($categories)
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->in('uid', $categories);

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        $query->setOrderings(
            ['sorting' => QueryInterface::ORDER_ASCENDING]
        );
        $categories = $query->execute();

        $flatCategories = [];
        foreach ($categories as $category) {
            $flatCategories[$category->getUid()] = [
                'item'   => $category,
                'parent' => ($category->getParent()->current()) ? $category->getParent()->current()->getUid() : null,
            ];
        }

        $tree = [];
        foreach ($flatCategories as $id => &$node) {
            if ($node['parent'] === null) {
                $tree[$id] = &$node;
            } else {
                $flatCategories[$node['parent']]['children'][$id] = &$node;
            }
        }

        return $tree;
    }

    /**
     * Finds all datasets of current branch and return in tree order
     *
     * @param \Slub\SlubEvents\Domain\Model\Category $startCategory
     * @Extbase\IgnoreValidation("startCategory")
     *
     * @return array The found Category Objects as Tree
     */
    public function findCurrentBranch($startCategory = null)
    {
        $childCategorieIds = $this->findAllChildCategories($startCategory->getUid());

        // ups, no children found...
        if (count($childCategorieIds) == 0) {
            return [];
        }

        $categories = $this->findAllByUids($childCategorieIds);

        $flatCategories = [];

        foreach ($categories as $category) {
            $flatCategories[$category->getUid()] = [
                'item'   => $category,
                'parent' => ($category->getParent()->current()) ? $category->getParent()->current()->getUid() : null,
            ];
        }

        $tree = [];
        foreach ($flatCategories as $id => &$node) {
            if ($node['parent'] === null) {
                $tree[$id] = &$node;
            } else {
                $flatCategories[$node['parent']]['children'][$id] = &$node;
                // if tree is empty, we have to add this node here too
                if (empty($tree)) {
                    $tree[$node['parent']]['children'][$id] = &$node;
                }
            }
        }

        return $tree[$startCategory->getUid()]['children'];
    }

    /**
     * Finds all datasets of current level and return in tree order
     *
     * @param integer $startCategory
     *
     * @return array The found Category Ids
     */
    public function findAllChildCategories($startCategory = 0)
    {
        $childCategoriesIds = $this->findChildCategories($startCategory);

        return $childCategoriesIds;
    }

    /**
     * Finds all categories recursive from given startCategory
     *
     * @param integer $startCategory
     *
     * @return array The found Category Ids
     */
    private function findChildCategories($startCategory = 0)
    {
        $query = $this->createQuery();

        $constraints = [];

        $constraints[] = $query->equals('parent', $startCategory);

        $query->setOrderings(
            ['sorting' => QueryInterface::ORDER_DESCENDING]
        );

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }
        $categories = $query->execute();

        $childCategoriesIds = [];

        foreach ($categories as $category) {
            $childCategoriesIds[] = $category->getUid();
            $recursiveCategoriesIds = self::findChildCategories($category->getUid());
            if (count($recursiveCategoriesIds) > 0) {
                $childCategoriesIds = array_merge($recursiveCategoriesIds, $childCategoriesIds);
            }
        }

        return $childCategoriesIds;
    }


    /**
     * Finds all datasets of current branch and return in tree order
     *
     * @param \Slub\SlubEvents\Domain\Model\Category $startCategory
     * @Extbase\IgnoreValidation("startCategory")
     *
     * @return array The found Category Objects
     */
    public function findCategoryRootline($startCategory = null)
    {
        $query = $this->createQuery();

        $constraints = [];

        if ($startCategory !== null) {
            $constraints[] = $query->equals('parents', $startCategory->getUid());
        } else {
            $constraints[] = $query->equals('parent', 0);
        }

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }
        $categories = $query->execute();

        $flatCategories = [];
        foreach ($categories as $category) {
            $flatCategories[$category->getUid()] = [
                'item'   => $category,
                'parent' => ($category->getParent()->current()) ? $category->getParent()->current()->getUid() : null,
            ];
        }

        $tree = [];

        // if only one categorie exists the foreach-solution below
        // doesn't work as expected --> take the one and give it back as tree-array()
        if (count($flatCategories) == 1) {
            $tree[0] = array_shift($flatCategories);
            return $tree;
        }

        foreach ($flatCategories as $id => &$node) {
            if ($node['parent'] === null) {
                $tree[$id] = &$node;
            } else {
                $flatCategories[$node['parent']]['children'][$id] = &$node;
            }
        }
        return $tree;
    }

    /**
     * Get default WiBa Category, which has no parents
     *
     * @return \Slub\SlubEvents\Domain\Model\Category The found Category
     */
    public function findDefaultGeniusbarCategory()
    {
        $query = $this->createQuery();
        $constraints = array();

        $constraints[] = $query->equals('parent', 0);
        $constraints[] = $query->equals('genius_bar', 1);
        $query->matching($query->logicalAnd($constraints));

        // there should be only one !
        $query->setLimit(1);

        $cats = $query->execute();
        return $cats[0];
    }
}
