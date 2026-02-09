import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useState } from 'react';
import { Trash2, Edit, Play } from 'lucide-react';
import schedulers from '@/routes/schedulers';
import clickup from '@/routes/clickup';

interface Scheduler {
    id: number;
    name: string;
    run_time: string;
    days_of_week: string[];
    last_run: string | null;
    is_active: boolean;
}

interface Props {
    schedulers: Scheduler[];
}

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

export default function SchedulerIndex({ schedulers: schedulerData }: Props) {
    const [selectedScheduler, setSelectedScheduler] = useState<Scheduler | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const { data, setData, post, put, processing, reset, errors } = useForm({
        name: '',
        run_time: '',
        days_of_week: [] as string[],
        is_active: true,
    });

    const openForCreate = () => {
        setSelectedScheduler(null);
        reset();
        setIsDialogOpen(true);
    };

    const openForEdit = (scheduler: Scheduler) => {
        setSelectedScheduler(scheduler);
        setData({
            name: scheduler.name,
            run_time: scheduler.run_time,
            days_of_week: scheduler.days_of_week,
            is_active: scheduler.is_active,
        });
        setIsDialogOpen(true);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedScheduler) {
            put(schedulers.update(selectedScheduler.id).url, {
                onSuccess: () => setIsDialogOpen(false),
            });
        } else {
            post(schedulers.store().url, {
                onSuccess: () => setIsDialogOpen(false),
            });
        }
    };

    const deleteScheduler = (id: number) => {
        if (confirm('Are you sure you want to delete this scheduler?')) {
            router.delete(schedulers.destroy(id).url);
        }
    };

    const toggleDay = (day: string) => {
        setData('days_of_week',
            data.days_of_week.includes(day)
                ? data.days_of_week.filter(d => d !== day)
                : [...data.days_of_week, day]
        );
    };

    const runNow = () => {
        if (confirm('Run report now?')) {
            router.get(clickup.sendReport().url);
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Schedulers', href: '/schedulers' }]}>
            <Head title="Schedulers" />

            <div className="p-6 space-y-6">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Daily Schedulers</h1>
                    <div className="space-x-2">
                        <Button variant="outline" onClick={runNow}>
                            <Play className="w-4 h-4 mr-2" />
                            Run Manual Report
                        </Button>
                        <Button onClick={openForCreate}>Add Scheduler</Button>
                    </div>
                </div>

                <div className="grid gap-4">
                    {schedulerData.map((scheduler: Scheduler) => (
                        <Card key={scheduler.id}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    {scheduler.name}
                                </CardTitle>
                                <div className="flex space-x-2">
                                    <Button variant="ghost" size="icon" onClick={() => openForEdit(scheduler)}>
                                        <Edit className="w-4 h-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" onClick={() => deleteScheduler(scheduler.id)}>
                                        <Trash2 className="w-4 h-4 text-red-500" />
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{scheduler.run_time}</div>
                                <p className="text-xs text-muted-foreground">
                                    Days: {scheduler.days_of_week.join(', ')}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Last Run: {scheduler.last_run ? new Date(scheduler.last_run).toLocaleString() : 'Never'}
                                </p>
                                <div className="mt-2 text-xs">
                                    Status: <span className={scheduler.is_active ? 'text-green-500' : 'text-red-500'}>
                                        {scheduler.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>

            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selectedScheduler ? 'Edit Scheduler' : 'Add New Scheduler'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="run_time">Run Time (HH:mm)</Label>
                            <Input
                                id="run_time"
                                type="time"
                                value={data.run_time}
                                onChange={e => setData('run_time', e.target.value)}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Days of Week</Label>
                            <div className="grid grid-cols-2 gap-2">
                                {DAYS.map(day => (
                                    <div key={day} className="flex items-center space-x-2">
                                        <Checkbox
                                            id={`day-${day}`}
                                            checked={data.days_of_week.includes(day)}
                                            onCheckedChange={() => toggleDay(day)}
                                        />
                                        <Label htmlFor={`day-${day}`}>{day}</Label>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) => setData('is_active', !!checked)}
                            />
                            <Label htmlFor="is_active">Active</Label>
                        </div>
                        <Button type="submit" className="w-full" disabled={processing}>
                            {selectedScheduler ? 'Update' : 'Create'} Scheduler
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
