@availability @availability_gearup
Feature: Testing that activity access can be based on quests

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname |
      | Course 1  | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
      | s1       | Student   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
      | s1       | c1     | student      |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And the following "block_gearup > quests" exist:
      | title          | course | instructions       | startmode |
      | Quest 1        | c1     | This is what to do | 1         |
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Quest 1          | s1     |
    And the following "activities" exist:
      | activity | course | name   | idnumber  |
      | page     | c1     | Page 1 | PAGE1     |
      | page     | c1     | Page 2 | PAGE2     |
      | page     | c1     | Page 3 | PAGE3     |
      | page     | c1     | Page 4 | PAGE4     |
      | page     | c1     | Page 5 | PAGE5     |
      | page     | c1     | Page 6 | PAGE6     |
    And the following "availability_gearup > restrictions" exist:
      | activity | mission  | mode                  |
      | PAGE1    | Quest 1  | hascompleted          |
      | PAGE2    | Quest 1  | hasstarted            |
      | PAGE3    | Quest 1  | hasbeenrecruitedfor   |
      | PAGE4    | Quest 1  | isassigned            |
      | PAGE5    | Quest 1  | isongoing             |
      | PAGE6    | Quest 1  | isfinished            |

  @javascript
  Scenario: Students access restriction for quests
    When I am on the "c1" "course" page logged in as "s1"
    Then I should not see "Page 1"
    And I should not see "Page 2"
    And I should see "Page 3"
    And I should see "Page 4"
    And I should not see "Page 5"
    And I should not see "Page 6"
    # Recruit accepts the quest.
    And I click on "Quest 1" "link"
    And I click on "Accept" "button" in the "You've got a quest!" "dialogue"
    And I click on "OK" "button" in the "You've got a quest!" "dialogue"
    And I should not see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should see "Page 5"
    And I should not see "Page 6"
    # Teacher completes the quest
    And I am on the "Quest 1" "block_gearup > quest recruits" page logged in as "t1"
    And I follow "Student One"
    And I press "Mark complete"
    And I click on "Mark complete" "button" in the "Mark complete" "dialogue"
    # Quest is completed
    And I am on the "c1" "course" page logged in as "s1"
    And I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should not see "Page 5"
    And I should not see "Page 6"
    # Recruits finished the quest.
    And I click on "Quest 1" "link"
    And I click on "Thank you" "button" in the "Quest 1" "dialogue"
    And I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should not see "Page 5"
    And I should see "Page 6"
