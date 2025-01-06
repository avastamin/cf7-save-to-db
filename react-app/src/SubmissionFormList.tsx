declare global {
  interface Window {
    cf7ReactPlugin: {
      apiUrl: string;
    };
  }
}
import {
  ChatBubbleLeftIcon,
  ChevronRightIcon,
} from "@heroicons/react/24/outline";
import { useState, useEffect } from "react";
import { Link } from "react-router-dom";

interface FormName {
  name: string;
  id: string;
  date: string;
  count: number;
}

const SubmissionFormList = () => {
  const [formNames, setFormNames] = useState<FormName[]>([]);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    const fetchSubmissions = async () => {
      try {
        const response = await fetch(
          `${window.cf7ReactPlugin.apiUrl}/wp-json/cf7/v1/form-names`
        );
        const data = await response.json();
        console.log("form names", data);
        setFormNames(data);
        setLoading(false);
      } catch (error) {
        console.error("Error fetching form names:", error);
        setLoading(false);
      }
    };

    fetchSubmissions();
  }, []);

  if (loading) {
    return <div>Loading submissions...</div>;
  }

  return (
    <div className="container mx-auto bg-white shadow rounded-lg mt-8">
      <div className="mb-3">
        <div className="max-w-full">
          <ul role="list" className="divide-y divide-gray-100">
            {formNames.map((formName, index) => (
              <Link to={`/form/${formName.id}/submission`}>
                <li
                  key={index}
                  className="flex flex-wrap items-center justify-between px-4 py-6 gap-x-6 gap-y-4 sm:flex-nowrap cursor-pointer hover:bg-gray-50"
                >
                  <div>
                    <p className="text-lg font-medium">{formName.name}</p>
                    <div className="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
                      <p>Submitted on</p>
                      <svg viewBox="0 0 2 2" className="size-0.5 fill-current">
                        <circle r={1} cx={1} cy={1} />
                      </svg>
                      <p>
                        <time dateTime={formName.date}>{formName.date}</time>
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center w-16 gap-x-2.5">
                    <dt>
                      <span className="sr-only">Total comments</span>
                      <ChatBubbleLeftIcon
                        aria-hidden="true"
                        className="size-6 text-gray-400"
                      />
                    </dt>
                    <dd className="text-sm/6 text-gray-900">
                      {formName.count}
                    </dd>
                    <ChevronRightIcon
                      aria-hidden="true"
                      className="size-5 flex-none text-gray-400"
                    />
                  </div>
                </li>
              </Link>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
};

export default SubmissionFormList;
