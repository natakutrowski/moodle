@block @block_gearup
Feature: Testing navigating through recruits
  In order to get information about users
  As a teacher
  I must be able to browse through them

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname |
      | Course 1  | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
      | s1       | Dylan     | Murphy   |
      | s2       | Maddy     | Cloud    |
      | s3       | Ralph     | Dalton   |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
      | s1       | c1     | student        |
      | s2       | c1     | student        |
      | s3       | c1     | student        |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And the following "block_gearup > achievements" exist:
      | title              | course | instructions       |
      | Ultra Achievement  | c1     | This is what to do |
    And the following "block_gearup > quests" exist:
      | title              | course | instructions       |
      | Ultra Quest        | c1     | This is what to do |
    And the following "block_gearup > recruits" exist:
      | mission              | user   |
      | Ultra Achievement    | s1     |
      | Ultra Achievement    | s2     |
      | Ultra Quest          | s3     |

  Scenario: Teacher can navigate from a course to all recruits
    Given I am on the "c1" "Course" page logged in as "t1"
    And I click on "Manage" "link" in the "Level Up Quest" "block"
    When I follow "Recruits"
    Then I should see "Showing 1 to 3 of 3"

  @javascript
  Scenario: Teacher can navigate to a mission's recruits
    Given I am on the "c1" "block_gearup > missions" page logged in as "t1"
    And I follow "Ultra Quest"
    When I follow "Recruits"
    Then I should see "Showing 1 to 1 of 1"
    And I should see "Ralph Dalton"

  Scenario: Teachers can view all recruits in the main recruits page
    Given I am on the "c1" "block_gearup > recruits" page logged in as "t1"
    Then I should see "Dylan Murphy"
    And I should see "Maddy Cloud"
    And I should see "Ralph Dalton"

  Scenario: Teachers can filter out all recruits by name
    Given I am on the "c1" "block_gearup > recruits" page logged in as "t1"
    And I set the field "term" to "Dylan Murphy"
    When I press "Apply"
    Then I should see "Dylan Murphy" in the "table" "css_element"
    And I should not see "Maddy Cloud"
    And I should not see "Ralph Dalton"

  Scenario: Teachers can filter out all recruits by partial first name
    Given I am on the "c1" "block_gearup > recruits" page logged in as "t1"
    And I set the field "term" to "Dyl"
    When I press "Apply"
    Then I should see "Dylan Murphy"
    And I should not see "Maddy Cloud"
    And I should not see "Ralph Dalton"

  Scenario: Teachers can filter out all recruits by partial last name
    Given I am on the "c1" "block_gearup > recruits" page logged in as "t1"
    And I set the field "term" to "Mur"
    When I press "Apply"
    Then I should see "Dylan Murphy"
    And I should not see "Maddy Cloud"
    And I should not see "Ralph Dalton"

  Scenario: Teachers can filter out all recruits by one initial
    Given I am on the "c1" "block_gearup > recruits" page logged in as "t1"
    And I set the field "term" to "m"
    When I press "Apply"
    Then I should see "Dylan Murphy"
    And I should see "Maddy Cloud"
    And I should not see "Ralph Dalton"

  Scenario: Teachers can filter out all recruits by both initials
    Given I am on the "c1" "block_gearup > recruits" page logged in as "t1"
    And I set the field "term" to "d m"
    When I press "Apply"
    Then I should see "Dylan Murphy"
    And I should not see "Maddy Cloud"
    And I should not see "Ralph Dalton"

  Scenario: Teachers can view recruits in the missions page
    Given I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    Then I should see "Dylan Murphy"
    And I should see "Maddy Cloud"
    And I should not see "Ralph Dalton"

  Scenario: Teachers can filter out recruits by name
    Given I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I set the field "term" to "Dylan Murphy"
    When I press "Apply"
    Then I should see "Dylan Murphy" in the "table" "css_element"
    And I should not see "Maddy Cloud"

  Scenario: Teachers can filter out recruits by partial first name
    Given I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I set the field "term" to "Dyl"
    When I press "Apply"
    Then I should see "Dylan Murphy"
    And I should not see "Maddy Cloud"

  Scenario: Teachers can filter out recruits by partial last name
    Given I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I set the field "term" to "Mur"
    When I press "Apply"
    Then I should see "Dylan Murphy"
    And I should not see "Maddy Cloud"

  Scenario: Teachers can filter out recruits by one initial
    Given I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I set the field "term" to "m"
    When I press "Apply"
    Then I should see "Dylan Murphy"
    And I should see "Maddy Cloud"

  Scenario: Teachers can filter out recruits by both initials
    Given I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I set the field "term" to "d m"
    When I press "Apply"
    Then I should see "Dylan Murphy"
    And I should not see "Maddy Cloud"
