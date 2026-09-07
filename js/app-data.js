/**
 * Antigo Web App - Shared Data Layer (Phase 1 LocalStorage Prototype)
 * Handles state persistence for Inquiries, Bookings, Projects, Messages, and Sessions.
 */

const AntigoData = {
    // Keys
    KEYS: {
        INQUIRIES: 'antigo_inquiries',
        BOOKINGS: 'antigo_bookings',
        PROJECTS: 'antigo_projects',
        MESSAGES: 'antigo_messages',
        CURRENT_USER: 'antigo_current_user'
    },

    // Seed Data
    init: function() {
        if (!localStorage.getItem(this.KEYS.INQUIRIES)) {
            const initialInquiries = [
                {
                    id: 'INQ-1001',
                    name: 'Maria Santos',
                    email: 'maria@pesolink.com',
                    company: 'Pesolink Financial',
                    projectType: 'UI Design',
                    budget: '₱150,000 – ₱300,000',
                    timeline: '1 Month',
                    description: 'Redesigning our mobile banking dashboard for smoother digital transactions and wallet management.',
                    status: 'reviewed', // 'new', 'reviewed', 'contacted', 'converted'
                    createdAt: '2026-09-01T09:30:00Z',
                    fileName: 'pesolink_brief_v1.pdf'
                },
                {
                    id: 'INQ-1002',
                    name: 'Jared Dela Cruz',
                    email: 'jared@kaperosters.ph',
                    company: 'Kape Roasters Co.',
                    projectType: 'Design Systems',
                    budget: '₱50,000 – ₱150,000',
                    timeline: '2–3 Months',
                    description: 'Building a unified digital storefront design system across our retail and subscription web platforms.',
                    status: 'converted',
                    createdAt: '2026-09-02T14:15:00Z',
                    fileName: 'kape_brand_assets.zip'
                },
                {
                    id: 'INQ-1003',
                    name: 'Anna Reyes',
                    email: 'anna.reyes@visayasclinic.ph',
                    company: 'Visayas Health Care',
                    projectType: 'UX Research',
                    budget: '₱50,000 – ₱150,000',
                    timeline: '1 Month',
                    description: 'Conducting patient user journey mapping and usability audits for our online clinic appointment system.',
                    status: 'new',
                    createdAt: '2026-09-04T10:00:00Z',
                    fileName: null
                }
            ];
            localStorage.setItem(this.KEYS.INQUIRIES, JSON.stringify(initialInquiries));
        }

        if (!localStorage.getItem(this.KEYS.BOOKINGS)) {
            const initialBookings = [
                {
                    id: 'BKG-2001',
                    inquiryId: 'INQ-1001',
                    clientName: 'Maria Santos',
                    clientEmail: 'maria@pesolink.com',
                    service: 'UI Design',
                    duration: '60 min',
                    price: '₱45,000',
                    date: '2026-09-10',
                    time: '10:00 AM',
                    format: 'Video Call (Google Meet)',
                    status: 'confirmed',
                    createdAt: '2026-09-01T11:00:00Z'
                },
                {
                    id: 'BKG-2002',
                    inquiryId: 'INQ-1003',
                    clientName: 'Anna Reyes',
                    clientEmail: 'anna.reyes@visayasclinic.ph',
                    service: 'UX Research',
                    duration: '30 min',
                    price: '₱30,000',
                    date: '2026-09-12',
                    time: '02:30 PM',
                    format: 'Video Call (Zoom)',
                    status: 'confirmed',
                    createdAt: '2026-09-04T10:30:00Z'
                }
            ];
            localStorage.setItem(this.KEYS.BOOKINGS, JSON.stringify(initialBookings));
        }

        if (!localStorage.getItem(this.KEYS.PROJECTS)) {
            const initialProjects = [
                {
                    id: 'PRJ-3001',
                    inquiryId: 'INQ-1001',
                    title: 'Pesolink Mobile Banking Redesign',
                    category: 'Fintech · Mobile App',
                    clientName: 'Maria Santos',
                    clientEmail: 'maria@pesolink.com',
                    company: 'Pesolink Financial',
                    budget: '₱250,000',
                    currentPhase: 3, // 1: Discovery, 2: Wireframing, 3: UI Design, 4: Prototyping, 5: Handover
                    phaseName: 'UI Design',
                    progress: 60,
                    status: 'In Design',
                    statusType: 'in_design', // 'pending', 'active', 'in_design', 'completed'
                    startDate: '2026-08-15',
                    dueDate: '2026-09-30',
                    internalNotes: 'Client requested extra focus on the biometric authentication flow and quick transfer shortcuts. High priority client.',
                    files: [
                        { name: 'Pesolink_Wireframes_v2.fig', size: '14.2 MB', date: 'Aug 24, 2026' },
                        { name: 'Design_Audit_Report.pdf', size: '3.8 MB', date: 'Aug 18, 2026' },
                        { name: 'Brand_Color_Tokens.json', size: '12 KB', date: 'Aug 20, 2026' }
                    ]
                },
                {
                    id: 'PRJ-3002',
                    inquiryId: 'INQ-1002',
                    title: 'Kape Roasters Design System',
                    category: 'Retail · Web Design System',
                    clientName: 'Jared Dela Cruz',
                    clientEmail: 'jared@kaperosters.ph',
                    company: 'Kape Roasters Co.',
                    budget: '₱120,000',
                    currentPhase: 5,
                    phaseName: 'Handover & Documentation',
                    progress: 100,
                    status: 'Delivered',
                    statusType: 'completed',
                    startDate: '2026-07-01',
                    dueDate: '2026-08-28',
                    internalNotes: 'Project completed ahead of schedule. Component library exported to Figma and CSS tokens delivered.',
                    files: [
                        { name: 'Kape_DesignSystem_Final.fig', size: '28.5 MB', date: 'Aug 28, 2026' },
                        { name: 'Component_Usage_Guide.pdf', size: '6.1 MB', date: 'Aug 28, 2026' }
                    ]
                },
                {
                    id: 'PRJ-3003',
                    inquiryId: null,
                    title: 'Clinic Booking & Patient Portal',
                    category: 'Healthcare · Web Platform',
                    clientName: 'Demo Client',
                    clientEmail: 'demo@client.com',
                    company: 'Visayas Health Care',
                    budget: '₱180,000',
                    currentPhase: 2,
                    phaseName: 'Wireframing & User Flows',
                    progress: 40,
                    status: 'In Review',
                    statusType: 'in_design',
                    startDate: '2026-08-20',
                    dueDate: '2026-10-10',
                    internalNotes: 'Patient reschedule flow reduced from 6 steps to 2 steps. Waiting for medical director approval.',
                    files: [
                        { name: 'Patient_Flowchart_v1.pdf', size: '2.4 MB', date: 'Aug 29, 2026' },
                        { name: 'Doctor_Dashboard_Wireframes.fig', size: '8.7 MB', date: 'Sep 02, 2026' }
                    ]
                }
            ];
            localStorage.setItem(this.KEYS.PROJECTS, JSON.stringify(initialProjects));
        }

        if (!localStorage.getItem(this.KEYS.MESSAGES)) {
            const initialMessages = {
                'PRJ-3001': [
                    { id: 1, sender: 'Kimberly Jayne Antigo', role: 'designer', text: 'Hi Maria! I have uploaded the updated dark/light mode toggle screens for the wallet view.', time: 'Aug 28, 10:14 AM' },
                    { id: 2, sender: 'Maria Santos', role: 'client', text: 'Looks fantastic Kimberly! Can we test a slightly higher contrast on the secondary transaction amounts?', time: 'Aug 28, 02:30 PM' },
                    { id: 3, sender: 'Kimberly Jayne Antigo', role: 'designer', text: 'Adjusted in Figma page 4! Let me know if you would like to review it on our scheduled consultation call.', time: 'Aug 29, 09:05 AM' }
                ],
                'PRJ-3003': [
                    { id: 1, sender: 'Kimberly Jayne Antigo', role: 'designer', text: 'Welcome to the project portal! I have mapped out the initial low-fidelity appointment calendar screens.', time: 'Aug 25, 11:00 AM' },
                    { id: 2, sender: 'Demo Client', role: 'client', text: 'Thanks Kimberly! The simplified 2-step reschedule process is exactly what we needed.', time: 'Aug 26, 03:15 PM' }
                ]
            };
            localStorage.setItem(this.KEYS.MESSAGES, JSON.stringify(initialMessages));
        }
    },

    // Inquiries
    getInquiries: function() {
        this.init();
        return JSON.parse(localStorage.getItem(this.KEYS.INQUIRIES) || '[]');
    },

    getInquiry: function(id) {
        return this.getInquiries().find(i => i.id === id);
    },

    addInquiry: function(inquiryData) {
        const list = this.getInquiries();
        const newId = 'INQ-' + (1000 + list.length + 1);
        const newInquiry = {
            id: newId,
            name: inquiryData.name,
            email: inquiryData.email,
            company: inquiryData.company || '',
            projectType: inquiryData.projectType,
            budget: inquiryData.budget,
            timeline: inquiryData.timeline,
            description: inquiryData.description,
            fileName: inquiryData.fileName || null,
            status: 'new',
            createdAt: new Date().toISOString()
        };
        list.unshift(newInquiry);
        localStorage.setItem(this.KEYS.INQUIRIES, JSON.stringify(list));
        return newInquiry;
    },

    updateInquiryStatus: function(id, status) {
        const list = this.getInquiries();
        const item = list.find(i => i.id === id);
        if (item) {
            item.status = status;
            localStorage.setItem(this.KEYS.INQUIRIES, JSON.stringify(list));
        }
        return item;
    },

    // Bookings
    getBookings: function() {
        this.init();
        return JSON.parse(localStorage.getItem(this.KEYS.BOOKINGS) || '[]');
    },

    addBooking: function(bookingData) {
        const list = this.getBookings();
        const newId = 'BKG-' + (2000 + list.length + 1);
        const newBooking = {
            id: newId,
            inquiryId: bookingData.inquiryId || null,
            clientName: bookingData.clientName,
            clientEmail: bookingData.clientEmail,
            service: bookingData.service,
            duration: bookingData.duration || '60 min',
            price: bookingData.price,
            date: bookingData.date,
            time: bookingData.time,
            format: bookingData.format || 'Video Call (Google Meet)',
            status: 'confirmed',
            createdAt: new Date().toISOString()
        };
        list.unshift(newBooking);
        localStorage.setItem(this.KEYS.BOOKINGS, JSON.stringify(list));

        // If linked to an inquiry, mark inquiry as contacted
        if (bookingData.inquiryId) {
            this.updateInquiryStatus(bookingData.inquiryId, 'contacted');
        }

        return newBooking;
    },

    // Projects
    getProjects: function() {
        this.init();
        return JSON.parse(localStorage.getItem(this.KEYS.PROJECTS) || '[]');
    },

    getProject: function(id) {
        return this.getProjects().find(p => p.id === id);
    },

    updateProjectPhase: function(id, phaseNumber, phaseName, statusText, statusType) {
        const list = this.getProjects();
        const project = list.find(p => p.id === id);
        if (project) {
            project.currentPhase = phaseNumber;
            project.phaseName = phaseName;
            project.progress = Math.min(100, Math.round((phaseNumber / 5) * 100));
            if (statusText) project.status = statusText;
            if (statusType) project.statusType = statusType;
            localStorage.setItem(this.KEYS.PROJECTS, JSON.stringify(list));
        }
        return project;
    },

    updateProjectNotes: function(id, notes) {
        const list = this.getProjects();
        const project = list.find(p => p.id === id);
        if (project) {
            project.internalNotes = notes;
            localStorage.setItem(this.KEYS.PROJECTS, JSON.stringify(list));
        }
        return project;
    },

    addProjectFile: function(id, fileObj) {
        const list = this.getProjects();
        const project = list.find(p => p.id === id);
        if (project) {
            if (!project.files) project.files = [];
            project.files.unshift(fileObj);
            localStorage.setItem(this.KEYS.PROJECTS, JSON.stringify(list));
        }
        return project;
    },

    // Messages
    getProjectMessages: function(projectId) {
        this.init();
        const all = JSON.parse(localStorage.getItem(this.KEYS.MESSAGES) || '{}');
        return all[projectId] || [];
    },

    addProjectMessage: function(projectId, sender, role, text) {
        this.init();
        const all = JSON.parse(localStorage.getItem(this.KEYS.MESSAGES) || '{}');
        if (!all[projectId]) all[projectId] = [];
        const msg = {
            id: Date.now(),
            sender: sender,
            role: role,
            text: text,
            time: 'Just now'
        };
        all[projectId].push(msg);
        localStorage.setItem(this.KEYS.MESSAGES, JSON.stringify(all));
        return msg;
    },

    // Auth & Session
    getCurrentUser: function() {
        const session = localStorage.getItem(this.KEYS.CURRENT_USER);
        if (session) return JSON.parse(session);
        // Default guest
        return null;
    },

    login: function(role, email) {
        const user = {
            role: role, // 'client' or 'admin'
            email: email,
            name: role === 'admin' ? 'Kimberly Jayne Antigo' : 'Demo Client',
            company: role === 'admin' ? 'Antigo UI/UX Advisory' : 'Visayas Health Care'
        };
        localStorage.setItem(this.KEYS.CURRENT_USER, JSON.stringify(user));
        return user;
    },

    logout: function() {
        localStorage.removeItem(this.KEYS.CURRENT_USER);
    }
};

// Initialize seed on script load
AntigoData.init();
