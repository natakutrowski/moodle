@availability @availability_gearup
Feature: Testing that activity access can be based on challenges

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
    And the following "block_gearup > challenges" exist:
      | title          | course | instructions       | repeatmode |
      | Challenge 1    | c1     | This is what to do | 0          |
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
      | PAGE1    | Challenge 1    | hascompleted          |
      | PAGE2    | Challenge 1    | hasstarted            |
      | PAGE3    | Challenge 1    | hasbeenrecruitedfor   |
      | PAGE4    | Challenge 1    | isassigned            |
      | PAGE5    | Challenge 1    | isongoing             |
      | PAGE6    | Challenge 1    | isfinished            |

  @javascript
  Scenario: Students access restriction for non-repeating challenges
    Given I am on the "Challenge 1" "block_gearup > challenge recruits" page logged in as "t1"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student One"
    And I press "Save changes"

    When I am on the "c1" "course" page logged in as "s1"
    Then I should not see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should see "Page 5"
    And I should not see "Page 6"

    And I am on the "Challenge 1" "block_gearup > challenge recruits" page logged in as "t1"
    And I follow "Student One"
    And I choose the "View" item in the "Menu" action menu
    And I press "Increment"
    And I press "Confirm"
    And I press "Finalise"
    And I click on "Finalise" "button" in the "Finalise mission" "dialogue"

    And I am on the "c1" "course" page logged in as "s1"
    And I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should not see "Page 5"
    And I should see "Page 6"

  @javascript
  Scenario: Students access restriction for repeating challenges
    Given I am on the "Challenge 1" "block_gearup > challenge" page logged in as "t1"
    And I click on "[data-form-class*=\"timing\"]" "css_element"
    And I set the field "Time to complete" to "1 day"
    And I click on "Repeat indefinitely" "radio"
    And I press "Save changes"
    And I follow "Recruits"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student One"
    And I press "Save changes"

    When I am on the "c1" "course" page logged in as "s1"
    Then I should not see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should see "Page 5"
    And I should not see "Page 6"

    And I am on the "Challenge 1" "block_gearup > challenge recruits" page logged in as "t1"
    And I follow "Student One"
    And I choose the "View" item in the "Menu" action menu
    And I press "Increment"
    And I press "Confirm"

    And I am on the "c1" "course" page logged in as "s1"
    And I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should not see "Page 5"
    And I should not see "Page 6"

    And I am on the "Challenge 1" "block_gearup > challenge recruits" page logged in as "t1"
    And I follow "Student One"
    And I choose the "View" item in the "Menu" action menu
    And I press "Finalise"
    And I click on "Finalise" "button" in the "Finalise mission" "dialogue"

    And I am on the "c1" "course" page logged in as "s1"
    And I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"
    And I should not see "Page 4"
    And I should not see "Page 5"
    And I should see "Page 6"

    And I click on "Challenge 1" "link"
    And I should not see "Completed" in the "Challenge" "dialogue"
