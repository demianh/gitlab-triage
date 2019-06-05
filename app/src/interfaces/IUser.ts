
export default interface IUser {
	avatar_url: string;
	bio: string;
	can_create_group: boolean;
	can_create_project: boolean;
	color_scheme_id: number;
	confirmed_at: any|null
	created_at: string;
	current_sign_in_at: any|null
	email: string;
	external: boolean;
	extra_shared_runners_minutes_limit: any|null
	id: number;
	identities: any[];
	is_admin: boolean;
	last_activity_on: string;
	last_sign_in_at: any|null
	linkedin: string;
	location: any|null
	name: string;
	organization: any|null
	private_profile: any|null
	projects_limit: number;
	public_email: string;
	shared_runners_minutes_limit: any|null
	skype: string;
	state: string;
	theme_id: number;
	twitter: string;
	two_factor_enabled: boolean;
	username: string;
	web_url: string;
	website_url: string;
}