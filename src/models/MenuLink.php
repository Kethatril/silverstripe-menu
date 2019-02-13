<?php

namespace gorriecoe\Menu\Models;

use gorriecoe\Link\Models\Link;
use gorriecoe\Menu\Models\MenuSet;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * MenuLink
 *
 * @package silverstripe-menu
 */
class MenuLink extends Link implements
    ScaffoldingProvider
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'MenuLink';

    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Link';

    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Links';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Sort' => 'Int'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'MenuSet' => MenuSet::class,
        'Parent' => MenuLink::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Children' => MenuLink::class
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'TypeLabel' => 'Type',
        'LinkURL' => 'Link',
        'Children.Count' => 'Children'
    ];

    /**
     * Default sort ordering
     * @var array
     */
    private static $default_sort = ['Sort' => 'ASC'];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        if (!$this->isAllowedChildren()) {
            return $fields;
        }
        $fields->addFieldsToTab(
            'Root.' . _t(__CLASS__ . '.CHILDREN', 'Children'),
            [
                GridField::create(
                    'Children',
                    _t(__CLASS__ . '.CHILDREN', 'Children'),
                    $this->Children(),
                    GridFieldConfig_RecordEditor::create()
                        ->addComponent(new GridFieldOrderableRows())
                )
            ]
        );

        return $fields;
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->ParentID > 0) {
            $this->MenuSetID = $this->Parent()->MenuSetID;
        }
    }

    /**
     * Checks if the menu allows child links.
     * @return Boolean
     */
    public function isAllowedChildren()
    {
        return $this->MenuSet()->AllowChildren;
    }

    /**
     * Relationship accessor for Graphql
     * @return MenuLink
     */
    public function getParent()
    {
        if ($this->ParentID) {
            return $this->Parent();
        }
    }

    /**
     * Returns the classes for this link.
     * @return string
     */
    public function getClass()
    {
        $this->setClass($this->LinkingMode());
        return parent::getClass();
    }

    /**
     * DataObject view permissions
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        if($this->MenuSet()->exists()) {
            return $this->MenuSet()->canEdit($member);
        }
        return true;
    }

    /**
     * DataObject edit permissions
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        if($this->MenuSet()->exists()) {
            return $this->MenuSet()->canEdit($member);
        }
        return true;
    }

    /**
     * DataObject delete permissions
     * @param Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return $this->MenuSet()->canEdit($member);
    }

    /**
     * DataObject create permissions
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        if (isset($context['Parent'])) {
            return $context['Parent']->canEdit();
        }
        if($this->MenuSet()->exists()) {
            return $this->MenuSet()->canEdit($member);
        }
        return true;
    }

    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        $scaffolder->type(MenuLink::class)
            ->addAllFields()
            ->addFields(['LinkURL'])
            ->nestedQuery('Children')
                ->setUsePagination(false)
                ->end()
            ->operation(SchemaScaffolder::READ)
                ->setName('readMenuLinks')
                ->setUsePagination(false)
                ->end()
            ->operation(SchemaScaffolder::READ_ONE)
                ->setName('readOneMenuLink')
                ->end()
            ->operation(SchemaScaffolder::CREATE)
                ->setName('createMenuLink')
                ->end()
            ->operation(SchemaScaffolder::UPDATE)
                ->setName('updateMenuLink')
                ->end()
            ->operation(SchemaScaffolder::DELETE)
                ->setName('deleteMenuLink')
                ->end()
            ->end();
        return $scaffolder;
    }
}
