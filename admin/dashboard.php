<?php include('header.php'); ?>
<div id="content-area">
	<div class="dashboard-column" id="left-column">
		<div class="dashboard-block c3" id="welcome">
			<?php
				$user= User::identify();
				if ( ! isset( $user->info->experience_level ) ) {
			?>
					<p><em>Welcome to Habari! This is the first time you've been here, so a quick tour is in order.</em></p>
					<p>In the top right corner of the window you'll find &ldquo;Admin&rdquo;, &ldquo;Publish&rdquo;, and &ldquo;Manage&rdquo;, plus the logout button. (Use that if you're sharing this computer, or paranoid, or just like pushing buttons.)</p>
					<p>Admin has 5 options. Clicking on &ldquo;Admin&rdquo; takes you back here. &ldquo;Options&rdquo; lets you make changes to the entire blog (Title, tagline, that sort of thing). &ldquo;Plugins&rdquo; is where you control, well, plugins. There are a few included, and there are dozens more <!-- a href='link to plugin site' -->plugins<!-- a/ --> available. &ldquo;Themes&rdquo; is where you can change how your blog looks to visitors. Again, a few are provided, but there are lots of <!-- a href='link to theme site' -->themes<!-- a/ --> out there. &ldquo;Users&rdquo; is where you control what the registered visitors, authors, and fellow admins can do on the site. Finally &ldquo;Import&rdquo; allows you to bring in your posts from another blogging platform. Just because you're using Habari doesn't mean you have to lose your old work.</p>
					<p>Next is &ldquo;Publish&rdquo;. You can work on posts or pages. Posts are like journal entries and are filed chronologically. Pages are filed seperately and are great for things like telling about the authors on your site.</p>
					<p>Finally, you have the &ldquo;Manage&rdquo; option which includes &ldquo;Content&rdquo; where you can edit and delete posts and pages. You can also choose &ldquo;Comments&rdquo; where you can edit and delete comments. The last option is &ldquo;Spam&rdquo;. Here you can quickly review and destroy the spam that we've trapped.</p>
					<p>Below this message is your &ldquo;Dashboard&rdquo; where you can get a quick overview of what's been happening around <?php Options::out('title'); ?>.</p>
					<p>If this hasn't covered everything you need to know, there is a <a href="<?php Site::out_url('system'); ?>/help/index.html" onclick="popUp(this.href);return false;" title="The Habari Help Center">Help Center</a> link at the bottom of every page in the admin area. The next time you visit, you'll get a more condensed version of this message.</p>
			<?php
					$user->info->experience_level= 'user';
					$user->info->commit();
				}
				elseif ( $user->info->experience_level == 'user' ) {
			?>
					<p>Good to see you again, <?php echo $user->username; ?>! This is a quick pointer to help you find things like <a href="<?php Site::out_url('system'); ?>/help/index.html" onclick="popUp(this.href);return false;" title="The Habari Help Center">Help</a>, <a href="<?php URL::out('admin', 'page=themes')?>" title="Manage your themes">themes</a>, and <a href="<?php URL::out('admin', 'page=plugins')?>" title="Manage your plugins">plugins</a>. Before you go back to creating your masterpiece, you might take a look at what's been happening around <?php Options::out('title'); ?>. When you've done that you can <a href="<?php URL::out('admin', 'page=publish&type=entry'); ?>" title="Post an Entry">post an entry</a> or <a href="<?php URL::out('admin', 'page=moderate')?>" title="Manage Comments">manage your comments</a>.</p>
			<?php
				}
				else {
			?>
					<p>Welcome back, <?php echo $user->username; ?>! If you need <a href="<?php Site::out_url('system')?>/help/index.html" onclick="popUp(this.href);return false;" title="The Habari Help Center">Help</a>, it's always available.</p>
			<?php
				 }
			?>
		</div>
		<div class="dashboard-block" id="stats">
			<h4>Site Statistics</h4>
				<ul id="site-stats">
					<li><span class="right"><?php echo Posts::count_total( Post::status('all') ); ?></span> Total Posts</li>
					<li><span class="right"><?php echo Posts::count_by_author( User::identify()->id, Post::status('all') ); ?></span> Number of Your Posts</li>
					<li><span class="right"><?php echo Comments::count_total(); ?></span> Number of Comments</li>
				</ul>
		</div>
		<div class="dashboard-block" id="system-info">
			<h4>System Health</h4>
			<ul>
				<li>&raquo; You are running Habari <?php echo Version::get_habariversion() ?>.</li>
			<?php
			$updates = Update::check();
			//Utils::debug($updates);  //Uncomment this line to see what Update:check() returns...
			if(count($updates) > 0) :
				foreach($updates as $update) {
					$class = implode(' ', $update['severity']);
					if(in_array('critical', $update['severity'])) {
						$updatetext = _t('<a href="%1s">%2s %3s</a> is a critical update.');
					}
					elseif(count($update['severity']) > 1) {
						$updatetext = _t('<a href="%1s">%2s %3s</a> contains bug fixes and additional features.');
					}
					elseif(in_array('bugfix', $update['severity'])) {
						$updatetext = _t('<a href="%1s">%2s %3s</a> contains bug fixes.');
					}
					elseif(in_array('feature', $update['severity'])) {
						$updatetext = _t('<a href="%1s">%2s %3s</a> contains additional features.');
					}
					$updatetext = sprintf($updatetext, $update['url'], $update['name'], $update['latest_version']);
					echo "<li class=\"{$class}\">&raquo; {$updatetext}</li>";
				}
			else:
				echo "<li>No updates were found.</li>";
			endif;
			?>
			</ul>
		</div>
		<div class="dashboard-block" id="incoming">
			<h4>Incoming Links (<a href="http://blogsearch.google.com/?scoring=d&amp;num=10&amp;q=link:<?php Site::out_url('hostname') ?>" title="More incoming links">more</a> &raquo;)</h4>
			<?php
			// This should be fetched on a pseudo-cron and cached:
			$search = new RemoteRequest('http://blogsearch.google.com/blogsearch_feeds?scoring=d&num=10&output=atom&q=link:' . Site::get_url('hostname') );
			$search->__set_processor( new SocketRequestProcessor );
			$search->set_timeout( 5 );
			$result= $search->execute();
			if ( $search->executed() ) {
				$xml = new SimpleXMLElement($search->get_response_body());
				if(count($xml->entry) == 0) {
					_e( '<p>No incoming links were found to this site.</p>' );
				}
				else {
			?>
			<ul id="incoming-links">
				<?php foreach($xml->entry as $entry) { ?>
				<li>
					<!-- need favicon discovery and caching here: img class="favicon" src="http://skippy.net/blog/favicon.ico" alt="favicon" / -->
					<a href="<?php echo $entry->link['href']; ?>" title="<?php echo $entry->title; ?>"><?php echo $entry->title; ?></a>
				</li>
				<?php } ?>
			</ul>
			<?php
				}
			}
			else {
				_e( '<p>Error fetching links.</p>' );
			}
			?>
		</div>
		<div class="dashboard-block c2" id="drafts">
				<h4>Drafts (<a href="manage/drafts" title="View Your Drafts">more</a> &raquo;)</h4>
				<ul id="site-drafts">
				<?php
					if ( Posts::count_total( Post::status('draft') ) ) {
						foreach ( Posts::by_status( Post::status('draft') ) as $draft ) {
				?>
					<li>
						<span class="right">
							<a href="<?php echo $draft->permalink; ?>" title="View <?php echo $draft->title; ?>">
								<img src="<?php Site::out_url('admin_theme'); ?>/images/view.png" alt="View this draft" />
							</a>
							<a href="<?php URL::out('admin', 'page=publish&slug=' . $draft->slug); ?>" title="Edit <?php echo $draft->title; ?>">
								<img src="<?php Site::out_url('admin_theme'); ?>/images/edit.png" alt="Edit this draft" />
							</a>
						</span>
						<?php echo $draft->title; ?>
					</li>
				<?php
						}
				?>
				</ul>
				<?php
					}
					else {
						_e('<p>There are currently no drafts in process</p>');
					}
				?>
		</div>
		<div class="dashboard-block c2" id="recent-comments">
			<h4>Recent Comments
				<?php
					if ( Comments::count_total( Comment::STATUS_UNAPPROVED ) ) {
				?>
				(<a href="<?php URL::out('admin', array('page'=>'moderate', 'option'=>'comments'));?>" title="View Comments Awaiting Moderation "><?php echo Comments::count_total( Comment::STATUS_UNAPPROVED ); ?> comments awaiting moderation</a> &raquo;)
				<?php
					}
				?>
			</h4>
			<?php
				if ( Comments::count_total( Comment::STATUS_APPROVED ) ) {
			?>
				<table name="comment-data" width="100%" cellspacing="0">
					<thead>
						<tr>
							<th colspan="1" align="left">Post</th>
							<th colspan="1" align="left">Name</th>
							<th colspan="1" align="left">URL</th>
							<th colspan="1" align="center">Action</th>
						</tr>
					</thead>
					<?php
						foreach ( Comments::get( array( 'status' => Comment::STATUS_APPROVED, 'limit' => 5, 'orderby' => 'date DESC' ) ) as $recent ) {
							$post= Post::get( array( 'id' => $recent->post_id, ) );
					?>
					<tr>
						<td><?php echo $post->title; ?></td>
						<td><?php echo $recent->name; ?></td>
						<td><?php echo $recent->url; ?></td>
						<td align="center">
							<a href="<?php echo $post->permalink; ?>" title="View this post"><img src="<?php Site::out_url('admin_theme'); ?>/images/view.png" alt="View this comment" /></a>
							<img src="<?php Site::out_url('admin_theme'); ?>/images/edit.png" alt="Edit this comment" />
						</td>
					</tr>
					<?php } ?>
				</table>
				<?php
				}
				else {
					_e('<p>There are no comments to display</p>');
				}
				?>
		</div>
	</div>
</div>
<?php include('footer.php'); ?>
