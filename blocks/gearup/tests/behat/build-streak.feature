@block @block_gearup
Feature: Testing build up a streak
  In order for students to build streaks
  As a teacher
  I need to set them up

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
      | s1       | c1     | student        |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |

  @javascript
  Scenario: Students can build their streaks
    Given I am on the "c1" "block_gearup > streaks" page logged in as "t1"
    And I follow "Create a streak"
    And I set the field "title" to "Great Streak"
    And I press "Continue"
    And I press "New objective"
    And I click on "Access course" clickable element
    And I press "Save changes"
    And I press "Continue"
    And I press "Continue"
    And I set the field "instructions" to "Access the course to build your streak"
    And I press "Continue"
    And I follow "View streak"
    And I follow "Recruits"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student One"
    And I press "Save changes"

    When I am on the "c1" "Course" page logged in as "s1"
    Then I should see "Course streak"
    And I should see "0" in the "[id^='gu-streaks']" "css_element"
    And I reload the page
    And I should see "1" in the "[id^='gu-streaks']" "css_element"

    And I am on the "c1" "block_gearup > streaks" page logged in as "t1"
    And I follow "Great Streak"
    And I follow "Recruits"
    And I follow "Student One"
    And I choose the "View" item in the "Menu" action menu
    And I press "Finalise"
    And I click on "Finalise" "button" in the "Finalise mission" "dialogue"

    And I am on the "c1" "Course" page logged in as "s1"
    And I reload the page
    And I should see "2" in the "[id^='gu-streaks']" "css_element"
