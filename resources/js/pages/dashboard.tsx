import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { Upload, FileText, CheckCircle2, Plus } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface StatCard {
    title: string;
    value: number;
    icon: React.ReactNode;
    description: string;
}

export default function Dashboard() {
    // Mock data - in a real app, this would come from props passed by the backend
    const stats: StatCard[] = [
        {
            title: 'Total Uploads',
            value: 24,
            icon: <Upload className="h-4 w-4" />,
            description: 'Files uploaded this month',
        },
        {
            title: 'Total Matched',
            value: 156,
            icon: <CheckCircle2 className="h-4 w-4" />,
            description: 'Records successfully matched',
        },
        {
            title: 'Total New',
            value: 42,
            icon: <Plus className="h-4 w-4" />,
            description: 'New records added',
        },
        {
            title: 'Total Duplicates',
            value: 8,
            icon: <FileText className="h-4 w-4" />,
            description: 'Possible duplicates found',
        },
    ];

    const recentBatches = [
        {
            id: 1,
            fileName: 'customers_2024_01.xlsx',
            uploadDate: '2024-01-15',
            status: 'Completed',
            totalRecords: 250,
            matchedCount: 198,
        },
        {
            id: 2,
            fileName: 'vendors_update.csv',
            uploadDate: '2024-01-14',
            status: 'Completed',
            totalRecords: 85,
            matchedCount: 72,
        },
        {
            id: 3,
            fileName: 'products_sync.xlsx',
            uploadDate: '2024-01-13',
            status: 'Completed',
            totalRecords: 512,
            matchedCount: 489,
        },
        {
            id: 4,
            fileName: 'employees_hr.csv',
            uploadDate: '2024-01-12',
            status: 'Completed',
            totalRecords: 156,
            matchedCount: 145,
        },
        {
            id: 5,
            fileName: 'locations_master.xlsx',
            uploadDate: '2024-01-11',
            status: 'Completed',
            totalRecords: 48,
            matchedCount: 46,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Page Header */}
                <Heading
                    title="Dashboard"
                    description="Welcome back! Here's an overview of your data matching activity."
                />

                {/* Stats Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {stats.map((stat) => (
                        <Card key={stat.title}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
                                <div className="text-muted-foreground">{stat.icon}</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stat.value}</div>
                                <p className="text-xs text-muted-foreground">{stat.description}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Recent Batches Section */}
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Batches</CardTitle>
                        <CardDescription>
                            Your 5 most recent file uploads and their matching results
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left font-medium py-3 px-4">File Name</th>
                                        <th className="text-left font-medium py-3 px-4">Upload Date</th>
                                        <th className="text-left font-medium py-3 px-4">Status</th>
                                        <th className="text-left font-medium py-3 px-4">Total Records</th>
                                        <th className="text-left font-medium py-3 px-4">Matched</th>
                                        <th className="text-left font-medium py-3 px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentBatches.map((batch) => (
                                        <tr key={batch.id} className="border-b hover:bg-muted/50">
                                            <td className="py-3 px-4">{batch.fileName}</td>
                                            <td className="py-3 px-4">{batch.uploadDate}</td>
                                            <td className="py-3 px-4">
                                                <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    {batch.status}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4">{batch.totalRecords}</td>
                                            <td className="py-3 px-4">{batch.matchedCount}</td>
                                            <td className="py-3 px-4">
                                                <Button variant="ghost" size="sm">
                                                    View
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                {/* Quick Actions */}
                <div className="flex gap-3">
                    <Button size="lg" className="gap-2">
                        <Upload className="h-4 w-4" />
                        Upload New File
                    </Button>
                    <Button variant="outline" size="lg" className="gap-2">
                        <FileText className="h-4 w-4" />
                        View All Batches
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
