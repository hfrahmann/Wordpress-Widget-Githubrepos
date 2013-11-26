<?php
/*
Plugin Name: Wordpress Widget Githubrepos
Plugin URI: https://github.com/hfrahmann/Wordpress-Widget-Githubrepos
Description: A Wordpress widget that lists all public repositories from a user
Author: Hendrik Frahmann
Version: 1.0.0
Author URI: http://www.hendrik-frahmann.de
 */

class Wordpress_Widget_Githubrepos extends WP_Widget {

    public function __construct() {
        $widget_ops = array('classname' => 'Wordpress_Widget_Githubrepos',
            'description' => __('A Wordpress widget that lists all public repositories from a user','Wordpress_Widget_Githubrepos'));
        parent::__construct(
            'Wordpress_Widget_Githubrepos', // Base ID
            'Githubrepos', // Name
            $widget_ops
        );
    }

    /**
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
        extract($args);
        $username = $instance['username'];
        $title = $instance['title'];
        $limit = intval($instance['limit']);

        echo $before_widget;
        echo $before_title . $title . $after_title;

        $data = $this->getGithubData($username);

        $count = 0;

        echo "<ul>";
        foreach($data as $repo)
        {
            echo "<li>";
            echo "<p><a href=\"".$repo['html_url']."\">".$repo['name']."</a><br>";
            echo $repo['description'] . "</p>";
            echo "</li>";


            if($limit > 0)
            {
                $count++;
                if($count == $limit)
                    break;
            }
        }
        echo "</ul>";

        echo $after_widget;
    }

    /**
     * @param array $instance
     * @return string|void
     */
    public function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, array( 'username' => '', 'limit' => 0 , 'title' => 'GitHub'));
        $username = strip_tags($instance['username']);
        $limit = intval(strip_tags($instance['limit']));
        $title = strip_tags($instance['title']);

        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
        <p><label for="<?php echo $this->get_field_id('limit'); ?>">Limit: <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo esc_attr($limit); ?>" /></label></p>
        <p><label for="<?php echo $this->get_field_id('username'); ?>">Username: <input class="widefat" id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>" type="text" value="<?php echo esc_attr($username); ?>" /></label></p>
        <?php
    }

    /**
     * @param string $username
     * @return array
     */
    protected function getGithubData($username)
    {
        $transient = "Githubrepos_" . $username;

        $result = get_transient($transient);

        if($result == false || $result == null)
        {
            $githubUrl = "https://api.github.com/users/".urlencode($username)."/repos?sort=updated&direction=desc";

            $curlSession = curl_init();
            curl_setopt($curlSession, CURLOPT_URL, $githubUrl);
            curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlSession, CURLOPT_HTTPHEADER, array(
                'User-Agent: Wordpress-Plugin'
            ));


            $jsonString = curl_exec($curlSession);
            curl_close($curlSession);

           if($jsonString == FALSE)
               $jsonString = "";

            $jsonData = json_decode($jsonString, true);
            if($jsonData == null)
                $jsonData = array();

            $result = $this->parseGithubData($jsonData);

            set_transient( $transient, $result, 60*30 ); // 30 min.
        }
        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function parseGithubData(array $data)
    {
        $newData = array();
        foreach($data as $repo)
        {
            $newData[] = array(
                'id' => $repo['id'],
                'name' => $repo['name'],
                'html_url' => $repo['html_url'],
                'description' => $repo['description'],
            );
        }
        return $newData;
    }

}

add_action( 'widgets_init', create_function('', 'return register_widget("Wordpress_Widget_Githubrepos");') );

?>
