<?php

namespace Tests;

use LyonStahl\SoqlBuilder\Exceptions\InvalidQueryException;
use LyonStahl\SoqlBuilder\SoqlBuilder;
use PHPUnit\Framework\TestCase;

final class SoqlBuilderTest extends TestCase
{
    public function testBaseQuery()
    {
        $qb = SoqlBuilder::from('Account')
            ->addSelect(['Id', 'Name', 'Description'])
            ->where('Name', '=', 'Mikhail')
            ->orderBy('Name')
            ->limit(10)
            ->offset(15);

        $this->assertEquals("SELECT Id, Name, Description FROM Account WHERE Name = 'Mikhail' ORDER BY Name ASC LIMIT 10 OFFSET 15", (string) $qb);
    }

    public function testWithBoolean()
    {
        $qb = SoqlBuilder::from('Account')
            ->addSelect(['Id', 'Name', 'Description'])
            ->where('IsChecked', '=', true);

        $this->assertEquals('SELECT Id, Name, Description FROM Account WHERE IsChecked = true', $qb->toSoql());
    }

    public function testWithNumber()
    {
        $qb = SoqlBuilder::from('Account')
            ->addSelect(['Id', 'Name', 'Description'])
            ->where('Amount', '=', 0);

        $this->assertEquals('SELECT Id, Name, Description FROM Account WHERE Amount = 0', $qb->toSoql());
    }

    public function testWithSeveralOrders()
    {
        $qb = SoqlBuilder::from('Account')
            ->addSelect(['Id', 'Name', 'Description'])
            ->orderBy('Name')
            ->orderByDesc('Description');

        $this->assertEquals('SELECT Id, Name, Description FROM Account ORDER BY Name ASC, Description DESC',
            $qb->toSoql());
    }

    public function testOrWhere()
    {
        $qb = SoqlBuilder::from('Account')
            ->addSelect(['Id', 'Name', 'Description'])
            ->orWhere('A', '=', 'B')
            ->orWhere('C', '=', 'D');

        $this->assertEquals('SELECT Id, Name, Description FROM Account WHERE A = \'B\' OR C = \'D\'', $qb->toSoql());
    }

    public function testWhereIn()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect(['Id', 'Name'])
            ->where('G', '=', 'G')
            ->whereIn('Name', ['A', 'B', 'C']);

        $this->assertEquals("SELECT Id, Name FROM Acc WHERE G = 'G' AND Name IN ('A', 'B', 'C')", $qb->toSoql());
    }

    public function testWhereNotIn()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect(['Id', 'Name'])
            ->where('G', '=', 'G')
            ->whereNotIn('Name', ['A', 'B', 'C']);

        $this->assertEquals("SELECT Id, Name FROM Acc WHERE G = 'G' AND Name NOT IN ('A', 'B', 'C')", $qb->toSoql());
    }

    public function testOrWhereIn()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect(['Id', 'Name'])
            ->where('G', '=', 'G')
            ->orWhereIn('Name', ['A', 'B', 'C']);

        $this->assertEquals("SELECT Id, Name FROM Acc WHERE G = 'G' OR Name IN ('A', 'B', 'C')", $qb->toSoql());
    }

    public function testOrWhereNotIn()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect(['Id', 'Name'])
            ->where('G', '=', 'G')
            ->orWhereNotIn('Name', ['A', 'B', 'C']);

        $this->assertEquals("SELECT Id, Name FROM Acc WHERE G = 'G' OR Name NOT IN ('A', 'B', 'C')", $qb->toSoql());
    }

    public function testQueryWithoutFields()
    {
        $this->expectException(InvalidQueryException::class);
        SoqlBuilder::from('Account')
            ->orderBy('Name')
            ->orderByDesc('Description')
            ->toSoql();
    }

    public function testQueryWithoutSObject()
    {
        $this->expectException(InvalidQueryException::class);
        SoqlBuilder::select(['Id', 'Name', 'Description'])
            ->orderBy('Name')
            ->orderByDesc('Description')
            ->toSoql();
    }

    public function testAddSelection()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect(['Id', 'Name']);

        $qb->addSelect('Description');

        $this->assertEquals('SELECT Id, Name, Description FROM Acc', $qb->toSoql());
    }

    public function testWhereColumns()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect(['Id', 'Name'])
            ->whereMultiple([['A', '>', 3], ['B', '<', 8]]);

        $this->assertEquals('SELECT Id, Name FROM Acc WHERE A > 3 AND B < 8', $qb->toSoql());
    }

    public function testWhereWithNull()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect(['Id', 'Name'])
            ->where('A', '=', null);

        $this->assertEquals('SELECT Id, Name FROM Acc WHERE A = null', $qb->toSoql());
    }

    public function testWhereWithDate()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect(['Id', 'Name'])
            ->whereDate('A', '=', '2019-10-10');

        $this->assertEquals('SELECT Id, Name FROM Acc WHERE A = 2019-10-10', $qb->toSoql());
    }

    public function testOrWhereWithDate()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect(['Id', 'Name'])
            ->whereDate('A', '=', '2019-10-10')
            ->orWhereDate('B', '=', '2019-10-09');

        $this->assertEquals('SELECT Id, Name FROM Acc WHERE A = 2019-10-10 OR B = 2019-10-09', $qb->toSoql());
    }

    public function testDuplicateSelect()
    {
        $qb = SoqlBuilder::from('Acc')
            ->addSelect('Id');

        $qb->addSelect('Id');

        $this->assertEquals('SELECT Id FROM Acc', $qb->toSoql());
    }

    public function testWhereFunction()
    {
        $actual = SoqlBuilder::from('Object')
            ->addSelect('Id')
            ->whereFunction('F', 'func1', 'chs1')
            ->whereFunction('F', 'func2', 'chs2')
            ->whereFunction('F', 'func3', 'chs3', 'OR')
            ->whereFunction('F', 'func4', 'chs4')
            ->toSoql();

        $this->assertEquals(
            "SELECT Id FROM Object WHERE F func1('chs1') AND F func2('chs2') OR F func3('chs3') AND F func4('chs4')",
            $actual
        );
    }

    public function testWhereFunctionByArray()
    {
        $actual = SoqlBuilder::from('Object')
            ->addSelect('Id')
            ->whereFunction('F', 'func1', ['chs1;chs2', 'chs3', 'chs4'])
            ->toSoql();

        $this->assertEquals(
            "SELECT Id FROM Object WHERE F func1('chs1;chs2', 'chs3', 'chs4')",
            $actual
        );
    }

    public function testConditionalExpressionsCanBeGrouped()
    {
        $actual = SoqlBuilder::from('Androids__c')
            ->addSelect('Id')
            ->where('Warranty', '=', 'Expired')
            ->startWhere()
            ->orWhere('Warranty', '=', 'Active')
            ->where('Days_Left__c', '<=', '60')
            ->endWhere()
            ->toSoql();

        $this->assertEquals("SELECT Id FROM Androids__c WHERE Warranty = 'Expired' OR (Warranty = 'Active' AND Days_Left__c <= '60')", $actual);
    }

    public function testConditionalsCanbeGroupedAlone()
    {
        $actual = SoqlBuilder::from('Androids__c')
            ->addSelect('Id')
            ->where('Warranty', '=', 'Expired')
            ->startWhere()
            ->where('Days_Left__c', '<=', '60')
            ->endWhere()
            ->toSoql();

        $this->assertEquals("SELECT Id FROM Androids__c WHERE Warranty = 'Expired' AND (Days_Left__c <= '60')", $actual);
    }

    public function testGroupedConditionalExpressionsCanExistsInMultipleLocations()
    {
        $actual = SoqlBuilder::from('Androids__c')
            ->addSelect('Id')
            ->startWhere()
            ->where('Warranty', '=', 'Active')
            ->where('Days_Left__c', '<=', '60')
            ->endWhere()
            ->startWhere()
            ->orWhere('Warranty', '=', 'Expired')
            ->where('Days_Expired__c', '<=', '30')
            ->endWhere()
            ->toSoql();

        $this->assertEquals("SELECT Id FROM Androids__c WHERE (Warranty = 'Active' AND Days_Left__c <= '60') OR (Warranty = 'Expired' AND Days_Expired__c <= '30')", $actual);
    }

    public function testGroupedConditionalExpressionsCanBeNested()
    {
        $actual = SoqlBuilder::from('Androids__c')
            ->addSelect('Id')
            ->startWhere()
            ->startWhere()
            ->where('Warranty', '=', 'Active')
            ->where('Days_Left__c', '<=', '60')
            ->endWhere()
            ->startWhere()
            ->orWhere('Warranty', '=', 'Expired')
            ->where('Days_Expired__c', '<=', '30')
            ->endWhere()
            ->orWhere('Select_This_Anyway__c', '=', 'true')
            ->endWhere()
            ->toSoql();

        $this->assertEquals("SELECT Id FROM Androids__c WHERE ((Warranty = 'Active' AND Days_Left__c <= '60') OR (Warranty = 'Expired' AND Days_Expired__c <= '30') OR Select_This_Anyway__c = 'true')", $actual);
    }

    public function testMismatchedGroupingCountsThrowQueryException()
    {
        $this->expectException(InvalidQueryException::class);

        SoqlBuilder::from('Androids__c')
            ->addSelect('Id')
            ->startWhere()
            ->where('Warranty', '=', 'Active')
            ->toSoql();
    }

    public function testMissingObjectThrowsQueryException()
    {
        $this->expectException(InvalidQueryException::class);

        SoqlBuilder::from('')
            ->addSelect('Id')
            ->toSoql();
    }

    public function testMissingFieldsThrowsQueryException()
    {
        $this->expectException(InvalidQueryException::class);

        SoqlBuilder::from('Androids')
            ->toSoql();
    }
}
