@block @block_gearup @javascript @_file_upload
Feature: Taking the demo course
  In order to get to know Level Up Quest
  As an instructional designer
  I must be able to take the demo course

  Scenario: Taking the demo course
    Given I activate Level Up Quest
    And the following "users" exist:
      | username | firstname | lastname |
      | u1       | Demo      | Taker    |
    And the following "roles" exist:
      | shortname     | name          |
      | quest-manager | Quest manager |
    And the following "role capability" exists:
      | role                     | quest-manager |
      | block/gearup:manage      | allow         |
      | block/gearup:view        | allow         |
    # Set loglifetime to -1 to avoid issues stemming from events get_legacy_logdata.
    And the following config values are set as admin:
      | enableasyncbackup | 0  |
      | loglifetime       | -1 |
    And I log in as "admin"
    And I navigate to "Courses > Restore course" in site administration
    # Loosely target the first 'Manage...' button because the text is different in different versions.
    And I press "Manage"
    And I upload "blocks/gearup/tests/fixtures/demo-course.mbz" file to "Files" filemanager
    And I press "Save changes"
    And I restore "demo-course.mbz" backup into a new course using this options:
      | Schema | Course name       | Quest demo |
      | Schema | Course short name | c1         |
    And the following "course enrolments" exist:
      | course | user | role    |
      | c1     | u1   | student |

    When I am on the "c1" "Course" page logged in as "u1"
    Then "Level Up Quest" "block" should not be visible
    And I press "Talk to me"
    And I click on "Accept" "button" in the "You've got a quest!" "dialogue"
    And I click on "OK" "button" in the "You've got a quest!" "dialogue"

    And "Level Up Quest" "block" should be visible
    And I should see "Let's get acquainted" in the "Level Up Quest" "block"
    And I should not see "Quite an achievement" in the "Level Up Quest" "block"

    And I click on "In two minutes, what is Level Up Quest?" "link" in the "#region-main" "css_element"
    And I am on the "c1" course page
    And I am on the "What is a quest?" "page activity" page
    And I am on the "c1" course page
    And I should see "Brilliant" in the "Level Up Quest" "block"
    And I follow "Let's get acquainted"
    And I press "Thank you"

    And I should not see "Let's get acquainted" in the "Level Up Quest" "block"
    And I should see "Quite an achievement" in the "Level Up Quest" "block"
    And I should not see "Course achievements" in the "Level Up Quest" "block"
    And "Achievements, say what?" "section" should be visible
    And I follow "Quite an achievement"
    And I click on "OK" "button" in the "Quite an achievement" "dialogue"
    And I am on the "What are achievements?" "page activity" page
    And I am on the "c1" course page
    And I am on the "Example of achievements" "page activity" page
    And I am on the "c1" course page
    And I follow "Quite an achievement"
    And I click on "Thank you" "button" in the "Quite an achievement" "dialogue"

    And I should not see "Quite an achievement" in the "Level Up Quest" "block"
    And I should see "Get challenged!" in the "Level Up Quest" "block"
    And I should see "Course achievements" in the "Level Up Quest" "block"
    And I should not see "Course challenges" in the "Level Up Quest" "block"
    And I follow "Over-achiever"
    And "time" "css_element" in the "Achievement" "dialogue" should be visible
    And I click on "Close" "button" in the "Achievement" "dialogue"
    And I reload the page
    And I follow "Avid learner"
    And "time" "css_element" in the "Achievement" "dialogue" should not be visible
    And I click on "Close" "button" in the "Achievement" "dialogue"
    And "Challenge me, señor!" "section" should be visible
    # Skipping reading the quest.
    And I am on the "What are challenges?" "page activity" page
    And I am on the "c1" course page
    And I follow "Get challenged!"
    And I click on "Thank you" "button" in the "Get challenged!" "dialogue"

    And I should see "Course challenges" in the "Level Up Quest" "block"
    And I follow "Instructor in the making"
    And I click on "OK" "button" in the "Instructor in the making!" "dialogue"
    And I am on the "Recruiting learners" "page activity" page
    And I am on the "c1" course page
    And I am on the "Getting insights" "page activity" page
    And I am on the "c1" course page
    And I follow "Avid learner"
    And "time" "css_element" in the "Achievement" "dialogue" should be visible
    And I click on "Close" "button" in the "Achievement" "dialogue"
    And I follow "Instructor in the making!"
    And I click on "Thank you" "button" in the "Instructor in the making!" "dialogue"

    # Skipping reading the quest.
    And I should not see "Manage" in the "Level Up Quest" "block"
    And I click on "Getting promoted to Quest Manager!" "link" in the "#region-main" "css_element"
    And I am on the "c1" course page
    And I follow "Get promoted!"
    And I click on "Thank you" "button" in the "Get promoted!" "dialogue"
    And I should see "Manage" in the "Level Up Quest" "block"
    And I click on "Manage" "link" in the "Level Up Quest" "block"
