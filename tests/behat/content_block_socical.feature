@ou @ou_vle @mod @mod_openstudio @javascript @mod_openstudio_content_block_social
Feature: Open Studio notifications
  In order to track activity on content I am interested in
  As a student
  I want recive notifications about my posts and comments

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | teacher2 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "groups" exist:
      | name   | course | idnumber |
      | group1 | C1     | G1       |
    And the following "groupings" exist:
      | name      | course | idnumber |
      | grouping1 | C1     | GI1      |
    And the following "grouping groups" exist:
      | grouping | group |
      | GI1      | G1    |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
      | student1 | G1    |
      | student2 | G1    |
    And the following open studio "instances" exist:
      | course | name             | description                | grouping | groupmode | pinboard | idnumber | tutorroles     |
      | C1     | Demo Open Studio | Notifification description | GI1      | 1         | 99       | OS1      | editingteacher |
    And all users have accepted the plagarism statement for "OS1" openstudio
    Given I am on the "Demo Open Studio" "openstudio activity" page logged in as "student1"
    And I follow "Add new content"
    And I set the following fields to these values:
      | id_visibility_3 | 1           |
      | Title           | Module post |
      | Description     | Module post |
    And I press "Save"
    And I follow "My Content"
    And I follow "Add new content"
    And I set the following fields to these values:
      | id_visibility_3 | 1             |
      | Title           | Module post 1 |
      | Description     | Module post 1 |
    And I press "Save"

  Scenario: Pop-up comments in content block social
    When I am on the "Demo Open Studio" "openstudio activity" page logged in as "teacher1"
    # The post without the comments.
    Then I click on "//*[@class='openstudio-grid-item-content-detail-info-icon'][1]" "xpath_element"
    And I should see "There are no comments."
    # Add 4 comments to post.
    Then I click on "Module post 1" "link"
    And I wait until the page is ready
    And I press "Add new comment"
    And I set the field "Comment" to "Very iconic, also has an amazing interior. At the time of the opening in 1973, the Opera house with its unique sails and something went right 1."
    And I wait until the page is ready
    And I press "Post comment"
    And I reload the page
    And I press "Add new comment"
    And I set the field "Comment" to "Beautifully designed building"
    And I press "Post comment"
    And I reload the page
    And I press "Add new comment"
    And I set the field "Comment" to "Very iconic, also has an amazing interior. At the time of the opening in 1973, the Opera house with its unique sails and something went right 3."
    And I press "Post comment"
    And I reload the page
    And I press "Add new comment"
    And I set the field "Comment" to "Very iconic, also has an amazing interior. At the time of the opening in 1973, the Opera house with its unique sails and something went right 4."
    And I press "Post comment"
    And I reload the page
    And I am on the "Demo Open Studio" "openstudio activity" page
    Then I click on "//*[@class='openstudio-grid-item-content-detail-info-icon'][1]" "xpath_element"
    Then I should see "Comments on this post"
    And I should see "Teacher 1 commented on this post."
    And I should see "'Very iconic, also has an amazing interior. At the time of the opening in 1973, the Opera house with its unique sails a"
    And I should see "Less than a minute ago"
    And I should see "'Beautifully designed building'"
    And I should see "Add comment"
    #Click comment text and redirect to comment of the post.
    And I wait until the page is ready
    And I click on "'Beautifully designed building'" "link"
    And I wait until the page is ready
    And I should see "Beautifully designed building"
    And I reload the page
    And I press "Add new comment"
    And I set the field "Comment" to "Architectural masterpiece"
    And I press "Post comment"
    And I reload the page
    And I am on the "Demo Open Studio" "openstudio activity" page
    Then I click on "//*[@class='openstudio-grid-item-content-detail-info-icon'][1]" "xpath_element"
    And I wait until the page is ready
    And I should see "'Architectural masterpiece'"
    # Add comment with an other user "student1"
    Then I am on the "Demo Open Studio" "openstudio activity" page logged in as "student1"
    Then I click on "//*[@class='openstudio-grid-item-content-detail-info-icon'][1]" "xpath_element"
    And I wait until the page is ready
    # Add comment by press button "Add comment" inside the popup list of comment.
    And I click on "Add comment" "link"
    And I set the field "Comment" to "Very iconic, also has an amazing interior."
    And I press "Post comment"
    And I am on the "Demo Open Studio" "openstudio activity" page
    Then I click on "//*[@class='openstudio-grid-item-content-detail-info-icon'][1]" "xpath_element"
    And I should see "Student 1 commented on this post."
    And I should see "Very iconic, also has an amazing interior."
    And I should see "Less than a minute ago"

  Scenario: Interactive emoticons in content block social
    When I am on the "Demo Open Studio" "openstudio activity" page logged in as "teacher1"
    And I wait until the page is ready
    # The emoticons should be the gray icon when user doesn't react it.
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'inspired')]//span[contains(., '')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'inspired')]//img[contains(@src, 'inspiration_default_rgb_32px')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'smiles')]//span[contains(., '')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'smiles')]//img[contains(@src, 'participation_smiles_default_rgb_32px')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'favourites')]//span[contains(., '')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'favourites')]//img[contains(@src, 'favourite_default_rgb_32px')]" "xpath_element" should exist
    Then I click on "Module post 1" "link"
    And I wait until the page is ready
    And I click on "0 Favourites" "link"
    And I click on "0 Smiles" "link"
    And I click on "0 Inspired" "link"
    And I wait until the page is ready
    And I am on the "Demo Open Studio" "openstudio activity" page
    # The emoticons should be the blue icon when user reacts it.
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'inspired')]//span[contains(., '1')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'inspired')]//img[contains(@src, 'inspiration_hover_active_rgb_32px')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'smiles')]//span[contains(., '1')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'smiles')]//img[contains(@src, 'participation_hover_active_smiles_rgb_32px')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'favourites')]//span[contains(., '1')]" "xpath_element" should exist
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'favourites')]//img[contains(@src, 'favourite_hover_active_rgb_32px')]" "xpath_element" should exist
    Then I am on the "Demo Open Studio" "openstudio activity" page logged in as "student1"
    And I click on "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'inspired')]//span[contains(., '1')]" "xpath_element"
    And I click on "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'smiles')]//span[contains(., '1')]" "xpath_element"
    And I click on "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'favourites')]//span[contains(., '1')]" "xpath_element"
    Then I am on the "Demo Open Studio" "openstudio activity" page logged in as "teacher1"
    And I click on "Module post 1" "link"
    And I should see "2 Favourites"
    And I should see "2 Smiles"
    And I should see "2 Inspired"
    And I am on the "Demo Open Studio" "openstudio activity" page
    And I wait until the page is ready
    Then "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'inspired')]//span[contains(., '2')]" "xpath_element" should exist
    And "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'smiles')]//span[contains(., '2')]" "xpath_element" should exist
    And "//div[@class='openstudio-grid-item'][1]//span[contains(@class, 'favourites')]//span[contains(., '2')]" "xpath_element" should exist

  Scenario: Add comment individual upload from My module page
    When I am on the "Demo Open Studio" "openstudio activity" page logged in as "teacher1"
    And I wait until the page is ready
    Then "//*[@class='openstudio-grid-item'][1]//img[contains(@src, 'comments_default_rgb_32px')]" "xpath_element" should exist
    And I click on "//*[@class='openstudio-grid-item'][1]//img[contains(@src, 'comments_default_rgb_32px')]" "xpath_element"
    And I click on "Add comment" "link"
    And "Comment" "field" should exist
    And I should not see "Add new comment"
    And I set the field "Comment" to "Very iconic, also has an amazing interior."
    And I press "Post comment"
    And I should see "Very iconic, also has an amazing interior."

  @_file_upload @editor_tiny
  Scenario: Pop-up comments in content block social with image in comment text.
    When I log in as "student1"
    And I follow "Private files" in the user menu
    And I upload "mod/openstudio/tests/importfiles/test2.jpg" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I am on the "Demo Open Studio" "openstudio activity" page
    # Add new comment.
    And I follow "Module post 1"
    And I press "Add new comment"
    # Upload an image.
    And I expand all toolbars for the "Comment" TinyMCE editor
    And I click on the "Image" button for the "Comment" TinyMCE editor
    And I click on "Browse repositories" "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "test2.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "How would you describe this image to someone who can't see it?" to "An image"
    And I click on "Save" "button" in the "Image details" "dialogue"
    # Post comment.
    And I press "Post comment"
    And I am on the "Demo Open Studio" "openstudio activity" page
    And I click on "//*[@class='openstudio-grid-item-content-detail-info-icon'][1]" "xpath_element"
    Then I should see " [Image] "

  @_file_upload @editor_tiny
  Scenario: Pop-up comments in content block social with video in comment text.
    When I log in as "student1"
    And I follow "Private files" in the user menu
    And I upload "mod/openstudio/tests/importfiles/test.mp4" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I am on the "Demo Open Studio" "openstudio activity" page
    # Add new comment.
    And I follow "Module post 1"
    And I press "Add new comment"
    # Upload a video.
    And I expand all toolbars for the "Comment" TinyMCE editor
    And I click on the "Multimedia" button for the "Comment" TinyMCE editor
    And I click on "Browse repositories" "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "test.mp4" "link"
    And I click on "Select this file" "button"
    And I click on "Save" "button"
    # Post comment.
    And I press "Post comment"
    And I am on the "Demo Open Studio" "openstudio activity" page
    And I follow "Module post 1"
    Then "video[title='test']" "css_element" should exist

  Scenario: Check unique comment count.
    Given I am on the "Demo Open Studio" "openstudio activity" page logged in as "student1"
    # Add 2 comments to post by Student1.
    And I click on "Module post 1" "link"
    And I press "Add new comment"
    And I set the field "Comment" to "Very iconic, also has an amazing interior."
    And I press "Post comment"
    And I wait until the page is ready
    And I press "Add new comment"
    And I set the field "Comment" to "Beautifully designed building"
    And I press "Post comment"
    And I wait until the page is ready
    And I am on the "Demo Open Studio" "openstudio activity" page
    # Student 1 should see 2 of their comments when the unique comment count is not enabled.
    And I should see "2" in the "#openstudio_grid .openstudio-grid-item:nth-child(2) .content-block-social-group > span:first-child span.has-value" "css_element"
    And I am on the "Demo Open Studio" "openstudio activity" page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I click on "Expand all" "link" in the "region-main" "region"
    When I set the following fields to these values:
      | Enable folders       | 1 |
      | Unique comment count | 1 |
    And I press "Save and display"
    And I click on "Module post 1" "link"
    And I press "Add new comment"
    And I set the field "Comment" to "Very iconic, also has an amazing interior."
    And I wait until the page is ready
    And I press "Post comment"
    And I wait until the page is ready
    And I press "Add new comment"
    And I set the field "Comment" to "Beautifully designed building"
    And I press "Post comment"
    And I wait until the page is ready
    And I am on the "Demo Open Studio" "openstudio activity" page
    # Teacher1 commented on this post so the number of comments returned is 1.
    And I should see "1" in the "#openstudio_grid .openstudio-grid-item:nth-child(2) .content-block-social-group > span:first-child span.has-value" "css_element"
    And I am on the "Demo Open Studio" "openstudio activity" page logged in as "student1"
    # Student 1 will still see 2 new comments count from Teacher 1 when the unique comment count is enabled.
    And I should see "2" in the "#openstudio_grid .openstudio-grid-item:nth-child(2) .content-block-social-group  > span:first-child span.openstudio-grid-item-content-detail-info-text-new" "css_element"
    And I click on "#openstudio_grid .openstudio-grid-item:nth-child(2) .content-block-social-group  > span:first-child " "css_element"
    And I should see "Teacher 1 commented on this post."
    And I click on "Add comment" "link"
    And I wait until the page is ready
    And I set the field "Comment" to "Architectural masterpiece"
    And I press "Post comment"
    And I am on the "Demo Open Studio" "openstudio activity" page
     # Student 1 sees only 1 comment when the unique comment count is enabled because comments from the post owner are not counted.
    Then I should see "1" in the "#openstudio_grid .openstudio-grid-item:nth-child(2) .content-block-social-group > span:first-child span.has-value" "css_element"
