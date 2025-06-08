@availability @availability_gearup
Feature: Testing that activity access can be based on achievements

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
    And the following "block_gearup > achievements" exist:
      | title          | course | instructions       |
      | Achievement 1  | c1     | This is what to do |
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And the following "activities" exist:
      | activity | course | name   | idnumber  |
      | page     | c1     | Page 1 | PAGE1     |
      | page     | c1     | Page 2 | PAGE2     |
      | page     | c1     | Page 3 | PAGE3     |
      | page     | c1     | Page 4 | PAGE4     |
      | page     | c1     | Page 5 | PAGE5     |
      | page     | c1     | Page 6 | PAGE6     |
    And the following "availability_gearup > restrictions" exist:
      | activity | mission        | mode                  |
      | PAGE1    | Achievement 1  | hascompleted          |
      | PAGE2    | Achievement 1  | hasstarted            |
      | PAGE3    | Achievement 1  | hasbeenrecruitedfor   |
      | PAGE4    | Achievement 1  | isassigned            |
      | PAGE5    | Achievement 1  | isongoing             |
      | PAGE6    | Achievement 1  | isfinished            |

  @javascript
  Scenario: Students access restriction for achievements
    When I am on the "c1" "course" page logged in as "s1"
    Then I should not see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should see "Page 5"
    And I should not see "Page 6"

    And I am on the "Achievement 1" "block_gearup > achievement recruits" page logged in as "t1"
    And I follow "Student One"
    And I press "Mark complete"
    And I click on "Mark complete" "button" in the "Mark complete" "dialogue"

    And I am on the "c1" "course" page logged in as "s1"
    And I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should not see "Page 5"
    And I should see "Page 6"
