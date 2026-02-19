import { Head } from '@inertiajs/react';
import { Upload, FileText, CheckCircle, XCircle, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Mock data for statistics
const stats = [
    { title: 'Total Uploads', value: '24', icon: Upload, description: 'Files uploaded this month' },
    { title: 'Total Matched', value: '156', icon: CheckCircle, description: 'Records successfully matched' },
    { title: 'Total New', value: '42', icon: FileText, description: 'New records added' },
    { title: 'Total Duplicates', value: '8', icon: XCircle, description: 'Duplicate records found' },
];

// Mock data for recent batches
const recentBatches = [
    { id: 1, fileName: 'customer_data_2024.csv', uploadDate: '2024-02-15', status: 'Completed', totalRecords: 1250, matched: 1180 },
    { id: 2, fileName: 'sales_records.xlsx', uploadDate: '2024-02-14', status: 'Processing', totalRecords: 890, matched: 845 },
    { id: 3, fileName: 'inventory_update.csv', uploadDate: '2024-02-13', status: 'Completed', totalRecords: 2100, matched: 2050 },
    { id: 4, fileName: 'employee_list.xlsx', uploadDate: '2024-02-12', status: 'Failed', totalRecords: 450, matched: 0 },
    { id: 5, fileName: 'product_catalog.csv', uploadDate: '2024-02-11', status: 'Completed', totalRecords: 3200, matched: 3150 },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {stats.map((stat) => {
                        const Icon = stat.icon;
                        return (
                            <Card key={stat.title}>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
                                    <Icon className="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{stat.value}</div>
                                    <p className="text-xs text-muted-foreground">{stat.description}</p>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Recent Batches Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Batches</CardTitle>
                        <CardDescription>Your most recent file uploads and their processing status</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left p-2 font-medium">File Name</th>
                                        <th className="text-left p-2 font-medium">Upload Date</th>
                                        <th className="text-left p-2 font-medium">Status</th>
                                        <th className="text-right p-2 font-medium">Total Records</th>
                                        <th className="text-right p-2 font-medium">Matched</th>
                                        <th className="text-center p-2 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentBatches.map((batch) => (
                                        <tr key={batch.id} className="border-b hover:bg-muted/50">
                                            <td className="p-2">{batch.fileName}</td>
                                            <td className="p-2">{batch.uploadDate}</td>
                                            <td className="p-2">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${batch.status === 'Completed'
                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                                        : batch.status === 'Processing'
                                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                                                            : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                                        }`}
                                                >
                                                    {batch.status}
                                                </span>
                                            </td>
                                            <td className="p-2 text-right">{batch.totalRecords.toLocaleString()}</td>
                                            <td className="p-2 text-right">{batch.matched.toLocaleString()}</td>
                                            <td className="p-2 text-center">
                                                <Button variant="ghost" size="sm">
                                                    <Eye className="h-4 w-4" />
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
                <div className="flex gap-4">
                    <Button>
                        <Upload className="mr-2 h-4 w-4" />
                        Upload New File
                    </Button>
                    <Button variant="outline">
                        <FileText className="mr-2 h-4 w-4" />
                        View All Batches
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
