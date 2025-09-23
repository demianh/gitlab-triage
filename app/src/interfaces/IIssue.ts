import IIssueUser from "@/interfaces/IIssueUser";

export default interface IIssue {
	id: string;
	iid: number;
	title: string;
	description: string;
	state: "opened"|"closed";
	createdAt: string;
	webUrl: string;
	weight: number | null;
	status: {
		id: string;
		name: string;
		color: string;
	} | null;
	milestone: {
		id: string;
		iid: string;
		title: string;
	} | null;
	labels: {
		nodes: {
			id: string;
			title: string;
			color: string;
		}[]
	};
	assignees: {
		nodes: IIssueUser[];
	};
	notes: {
		count: number;
	};
	author: IIssueUser;
}
