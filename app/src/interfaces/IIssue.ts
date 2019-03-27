import IIssueUser from "@/interfaces/IIssueUser";

export default interface IIssue {
	id: number;
	iid: number;
	project_id: number;
	title: string;
	description: string;
	state: "opened"|"closed";
	created_at: string;
	updated_at: string;
	closed_at: string;
	closed_by: IIssueUser;
	labels: string[];
	milestone: {
		id: number;
		iid: number;
		project_id: number;
		title: string;
		description: string;
		state: string;
		created_at: string;
		updated_at: string;
		due_date: string;
		start_date: string;
		web_url: string;
	};
	assignees: IIssueUser[];
	author: IIssueUser;
	assignee: IIssueUser;
	user_notes_count: number;
	upvotes: number;
	downvotes: number;
	due_date: string;
	confidential: boolean;
	discussion_locked: any;
	web_url: string;
	time_stats: {
		time_estimate: number;
		total_time_spent: number;
		human_time_estimate: null;
		human_total_time_spent: null
	};
	_links: {
		self: string;
		notes: string;
		award_emoji: string;
		project: string;
	};
	subscribed: boolean;
	weight: number;
}